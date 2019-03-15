<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnCellIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\RowCellIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\RowIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Hoogi91\Spreadsheets\Domain\Model\CellValue;
use Hoogi91\Spreadsheets\Domain\Model\SpreadsheetValue;
use Hoogi91\Spreadsheets\Traits\SheetIndexTrait;

/**
 * Class ExtractorService
 * @package Hoogi91\Spreadsheets\Service
 */
class ExtractorService
{
    use SheetIndexTrait;

    const EXTRACT_DIRECTION_HORIZONTAL = 'horizontal';
    const EXTRACT_DIRECTION_VERTICAL = 'vertical';

    /**
     * @var CellService
     */
    protected $cellService;

    /**
     * @var StyleService
     */
    protected $styleService;

    /**
     * @var SpanService
     */
    protected $spanService;

    /**
     * @var RangeService
     */
    protected $rangeService;

    /**
     * ExtractorService constructor.
     *
     * @param Spreadsheet $spreadsheet
     * @param int         $sheetIndex
     *
     * @throws SpreadsheetException
     */
    public function __construct(Spreadsheet $spreadsheet, int $sheetIndex = 0)
    {
        if ($sheetIndex < 0) {
            throw new \InvalidArgumentException(
                'sheet index must be positive integer!',
                1515668054
            );
        }

        $this->setSpreadsheet($spreadsheet);
        $this->setSheetIndex($sheetIndex);

        // initialize services
        $this->cellService = GeneralUtility::makeInstance(CellService::class, $this->getSpreadsheet());
        $this->styleService = GeneralUtility::makeInstance(StyleService::class, $this->getSpreadsheet());
        $this->spanService = GeneralUtility::makeInstance(SpanService::class, $this->getSpreadsheet());
        $this->rangeService = GeneralUtility::makeInstance(RangeService::class, $this->getSpreadsheet());
    }

    /**
     * @param SpreadsheetValue $value
     *
     * @return ExtractorService
     */
    public static function createFromSpreadsheetValue(SpreadsheetValue $value)
    {
        if (!$value->getFileReference() instanceof FileReference) {
            return null;
        }

        $readerService = GeneralUtility::makeInstance(ReaderService::class, $value->getFileReference());
        return GeneralUtility::makeInstance(
            ExtractorService::class,
            $readerService->getSpreadsheet(),
            $value->getSheetIndex()
        );
    }

    /**
     * @param string $string
     *
     * @return ExtractorService
     */
    public static function createFromDatabaseString(string $string)
    {
        $value = SpreadsheetValue::createFromDatabaseString($string);
        if (!$value->getFileReference() instanceof FileReference) {
            return null;
        }

        $readerService = GeneralUtility::makeInstance(ReaderService::class, $value->getFileReference());
        return GeneralUtility::makeInstance(
            ExtractorService::class,
            $readerService->getSpreadsheet(),
            $value->getSheetIndex()
        );
    }

    /**
     * @return array
     */
    public function getHeadData(): array
    {
        try {
            $sheet = $this->getSpreadsheet()->getActiveSheet();
            if (!$sheet instanceof Worksheet) {
                return [];
            } elseif ($sheet->getPageSetup()->isRowsToRepeatAtTopSet() === false) {
                return [];
            }

            $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();
            $range = 'A1:' . $sheet->getHighestColumn() . ($rowsToRepeatAtTop[1] + 1);
            return $this->rangeToCellArray($range, true);
        } catch (SpreadsheetException $e) {
            // sheet or range of cells couldn't be loaded
            return [];
        }
    }

    /**
     * @return array
     */
    public function getBodyData(): array
    {
        try {
            $sheet = $this->getSpreadsheet()->getActiveSheet();

            if (!$sheet instanceof Worksheet) {
                return [];
            } elseif ($sheet->getPageSetup()->isRowsToRepeatAtTopSet() === false) {
                $range = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                return $this->rangeToCellArray($range, true);
            }

            $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();
            $range = 'A' . ($rowsToRepeatAtTop[1] + 1) . ':' . $sheet->getHighestColumn() . $sheet->getHighestRow();
            return $this->rangeToCellArray($range, true);
        } catch (SpreadsheetException $e) {
            // sheet or range of cells couldn't be loaded
            return [];
        }
    }

    /**
     * Create array from a range of cells.
     *
     * @param string $range             Range of cells (i.e. "A1:B10"), or just one cell (i.e. "A1")
     * @param bool   $returnCellRef     False - Return array of rows/columns indexed by number counting from zero
     *                                  True - Return rows and columns indexed by their actual row and column IDs
     * @param bool   $calculate
     * @param bool   $format
     * @param string $direction
     *
     * @return array
     * @throws SpreadsheetException
     */
    public function rangeToCellArray(
        string $range,
        bool $returnCellRef = false,
        $calculate = true,
        $format = true,
        $direction = self::EXTRACT_DIRECTION_HORIZONTAL
    ): array {
        // Identify the range that we need to extract from the worksheet
        list($rangeStart, $rangeEnd) = Coordinate::rangeBoundaries($this->rangeService->convert(
            $this->getSheetIndex(),
            $range
        ));

        // get calculated and formatted value from cell service
        $cellValueCallback = function ($cell) use ($calculate, $format) {
            return $this->cellService->getValue($cell, $calculate, $format);
        };

        // set ignored cells, cell iterator range and iterator type to use (column or row)
        if ($direction === self::EXTRACT_DIRECTION_VERTICAL) {
            $cellArray = $this->extractColumnBasedRange(
                Coordinate::stringFromColumnIndex($rangeStart[0]),
                Coordinate::stringFromColumnIndex($rangeEnd[0]),
                (int)$rangeStart[1],
                (int)$rangeEnd[1],
                $cellValueCallback
            );
        } else {
            $cellArray = $this->extractRowBasedRange(
                (int)$rangeStart[1],
                (int)$rangeEnd[1],
                Coordinate::stringFromColumnIndex($rangeStart[0]),
                Coordinate::stringFromColumnIndex($rangeEnd[0]),
                $cellValueCallback
            );
        }

        if ($returnCellRef === true) {
            $cellArray = $this->updateColumnIndexesFromString($cellArray, $direction);
        }
        return $cellArray;
    }

    /**
     * @param int           $startRow
     * @param int           $endRow
     * @param string        $startColumn
     * @param string        $endColumn
     * @param callable|null $callback
     *
     * @return array
     * @throws SpreadsheetException
     */
    protected function extractRowBasedRange(
        int $startRow,
        int $endRow,
        string $startColumn,
        string $endColumn,
        callable $callback = null
    ) {
        return $this->processIteratorCellsWithCallback(
            $this->getSpreadsheet()->getActiveSheet()->getRowIterator($startRow, $endRow),
            [$startColumn, $endColumn],
            $this->spanService->getIgnoredRows(),
            $callback
        );
    }

    /**
     * @param string        $startColumn
     * @param string        $endColumn
     * @param int           $startRow
     * @param int           $endRow
     * @param callable|null $callback
     *
     * @return array
     * @throws SpreadsheetException
     */
    protected function extractColumnBasedRange(
        string $startColumn,
        string $endColumn,
        int $startRow,
        int $endRow,
        callable $callback = null
    ) {

        return $this->processIteratorCellsWithCallback(
            $this->getSpreadsheet()->getActiveSheet()->getColumnIterator($startColumn, $endColumn),
            [$startRow, $endRow],
            $this->spanService->getIgnoredColumns(),
            $callback
        );
    }

    /**
     * @param RowIterator|ColumnIterator $iterator
     * @param array                      $cellIteratorArgs
     * @param array                      $ignoredCellLines
     * @param callable|null              $callback
     *
     * @return array
     * @throws SpreadsheetException
     */
    protected function processIteratorCellsWithCallback(
        \Iterator $iterator,
        array $cellIteratorArgs = [],
        array $ignoredCellLines = [],
        callable $callback = null
    ) {
        // get ignored and merged cells
        $ignoreCells = $this->spanService->getIgnoredCells();
        $mergedCells = $this->spanService->getMergedCells();

        $returnValue = [];
        foreach ($iterator as $line => $cells) {
            if (in_array($line, $ignoredCellLines)) {
                continue; // this row/column can be completely ignored
            }

            /** @var RowCellIterator|ColumnCellIterator $cellIterator */
            $cellIterator = $cells->getCellIterator(...$cellIteratorArgs);
            $cellIterator->setIterateOnlyExistingCells(false); // loop all cells ;)

            foreach ($cellIterator as $cellIndex => $cell) {
                $cellReference = $cellIterator instanceof ColumnCellIterator ? ($line . $cellIndex) : ($cellIndex . $line);
                if (in_array($cellReference, $ignoreCells)) {
                    continue; // ignore processing of this cell
                }

                $getCellArgs = [$cell, $mergedCells[$cellReference] ?? [], $callback];
                if ($cellIterator instanceof ColumnCellIterator) {
                    // column-based $line should be string and cellIndex is row integer
                    $returnValue[$line][(int)$cellIndex] = $this->getCellValueForRangeToCellIterator(...$getCellArgs);
                } else {
                    // row-based $line is integer and cellIndex should be column string
                    $returnValue[(int)$line][$cellIndex] = $this->getCellValueForRangeToCellIterator(...$getCellArgs);
                }
            }
        }
        return $returnValue;
    }

    /**
     * @param Cell          $cell
     * @param array         $mergeInformation
     * @param callable|null $valueCallback
     *
     * @return CellValue
     */
    protected function getCellValueForRangeToCellIterator(
        Cell $cell,
        array $mergeInformation = [],
        callable $valueCallback = null
    ) {
        /** @var CellValue $cellValue */
        $cellValue = GeneralUtility::makeInstance(CellValue::class, $cell);
        if (is_callable($valueCallback) === true) {
            $cellValue->setValue(call_user_func($valueCallback, $cell));
        }

        // add merge informations to cell value if available
        if (!empty($mergeInformation)) {
            $cellValue->setRowspan($mergeInformation['rowspan'] ?: 0);
            $cellValue->setColspan($mergeInformation['colspan'] ?: 0);
            $cellValue->setAdditionalStyleIndexes($mergeInformation['additionalStyleIndexes'] ?: []);
        }
        return $cellValue;
    }

    /**
     * @param array  $cellArray
     * @param string $direction
     *
     * @return array
     */
    protected function updateColumnIndexesFromString(array $cellArray, $direction = self::EXTRACT_DIRECTION_HORIZONTAL)
    {
        if ($direction === self::EXTRACT_DIRECTION_VERTICAL) {
            // just get all keys and values for array combine
            // before combine map all keys to get column index from string
            return array_combine(array_map(function ($key) {
                return Coordinate::columnIndexFromString($key);
            }, array_keys($cellArray)), array_values($cellArray));
        }

        // iterate all rows and do same column conversion as above
        foreach ($cellArray as $row => $columns) {
            $cellArray[$row] = array_combine(array_map(function ($key) {
                return Coordinate::columnIndexFromString($key);
            }, array_keys($columns)), array_values($columns));
        }

        return $cellArray;
    }

    /**
     * @param string $htmlIdentifier
     *
     * @return string
     */
    public function getStyles(string $htmlIdentifier = '')
    {
        return $this->styleService->setIdentifier($htmlIdentifier)->toString();
    }
}
