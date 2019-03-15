<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\CellIterator;
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

        if ($direction === self::EXTRACT_DIRECTION_VERTICAL) {
            $iterator = $this->getSpreadsheet()->getActiveSheet()->getColumnIterator(
                Coordinate::stringFromColumnIndex($rangeStart[0]),
                Coordinate::stringFromColumnIndex($rangeEnd[0])
            );
        } else {
            $iterator = $this->getSpreadsheet()->getActiveSheet()->getRowIterator($rangeStart[1], $rangeEnd[1]);
        }

        $cellArray = $this->iterateCellsWithCallback($iterator, function ($cell, $mergeInfo) use ($calculate, $format) {
            /** @var CellValue $cellValue */
            $cellValue = GeneralUtility::makeInstance(CellValue::class, $cell);
            $cellValue->setValue($this->cellService->getValue($cell, $calculate, $format));

            // add merge informations to cell value if available
            if (!empty($mergeInfo)) {
                $cellValue->setRowspan($mergeInfo['rowspan'] ?: 0);
                $cellValue->setColspan($mergeInfo['colspan'] ?: 0);
                $cellValue->setAdditionalStyleIndexes($mergeInfo['additionalStyleIndexes'] ?: []);
            }
            return $cellValue;
        });

        if ($returnCellRef === true) {
            $cellArray = $this->updateColumnIndexesFromString($cellArray, $direction);
        }

        return $cellArray;
    }

    /**
     * @param RowIterator|CellIterator $iterator
     * @param callable                 $callback
     *
     * @return array
     * @throws SpreadsheetException
     */
    protected function iterateCellsWithCallback(\Iterator $iterator, callable $callback)
    {
        // get ignored cell lines depending on iterator type
        if ($iterator instanceof CellIterator) {
            $ignoredCellLines = $this->spanService->getIgnoredColumns();
        } else {
            $ignoredCellLines = $this->spanService->getIgnoredRows();
        }

        // get ignored and merged cells
        $ignoreCells = $this->spanService->getIgnoredCells();
        $mergedCells = $this->spanService->getMergedCells();

        $returnValue = [];
        foreach ($iterator as $line => $cells) {
            if (in_array($line, $ignoredCellLines)) {
                // this row/column can be completely ignored
                continue;
            }

            $cellIterator = $cells->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // loop all cells ;)
            foreach ($cellIterator as $cellIndex => $cell) {
                $cellReference = $iterator instanceof CellIterator ? ($line . $cellIndex) : ($cellIndex . $line);
                if (in_array($cellReference, $ignoreCells)) {
                    // ignore processing of this cell
                    continue;
                }

                // get merge information to cell value if available
                $mergeInfo = [];
                if (array_key_exists($cellReference, $mergedCells)) {
                    $mergeInfo = $mergedCells[$cellReference];
                }

                if ($iterator instanceof CellIterator) {
                    // column-based $line should be string and cellIndex is row integer
                    $returnValue[$line][(int)$cellIndex] = call_user_func($callback, $cell, $mergeInfo);
                } else {
                    // row-based $line is integer and cellIndex should be column string
                    $returnValue[(int)$line][$cellIndex] = call_user_func($callback, $cell, $mergeInfo);
                }
            }
        }

        return $returnValue;
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
