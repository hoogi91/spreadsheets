<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
     *
     * @return array
     * @throws SpreadsheetException
     */
    public function rangeToCellArray(
        string $range,
        bool $returnCellRef = false,
        $calculate = true,
        $format = true
    ): array {
        // Identify the range that we need to extract from the worksheet
        list($rangeStart, $rangeEnd) = Coordinate::rangeBoundaries($this->rangeService->convert(
            $this->getSheetIndex(),
            $range
        ));

        // get span informations for current worksheet
        $ignoreRows = $this->spanService->getIgnoredRows();
        $ignoreCells = $this->spanService->getIgnoredCells();
        $mergedCells = $this->spanService->getMergedCells();

        $returnValue = [];
        $rowIterator = $this->getSpreadsheet()->getActiveSheet()->getRowIterator($rangeStart[1], $rangeEnd[1]);
        foreach ($rowIterator as $r => $row) {
            if (in_array($r, $ignoreRows)) {
                // this row can completly be ignored
                continue;
            }

            $cellIterator = $row->getCellIterator(
                Coordinate::stringFromColumnIndex($rangeStart[0]),
                Coordinate::stringFromColumnIndex($rangeEnd[0])
            );
            $cellIterator->setIterateOnlyExistingCells(false); // loop all cells ;)
            foreach ($cellIterator as $c => $cell) {
                $cellReference = $c . $r;
                if (in_array($cellReference, $ignoreCells)) {
                    // ignore processing of this column
                    continue;
                }

                /** @var CellValue $cellValue */
                $cellValue = GeneralUtility::makeInstance(CellValue::class, $cell);
                $cellValue->setValue($this->cellService->getValue($cell, $calculate, $format));

                // add merge informations to cell value if available
                if (array_key_exists($cellReference, $mergedCells)) {
                    $cellValue->setRowspan($mergedCells[$cellReference]['rowspan'] ?: 0);
                    $cellValue->setColspan($mergedCells[$cellReference]['colspan'] ?: 0);
                    $cellValue->setAdditionalStyleIndexes($mergedCells[$cellReference]['additionalStyleIndexes'] ?: []);
                }

                if ($returnCellRef === true) {
                    $returnValue[(int)$r][Coordinate::columnIndexFromString($c)] = $cellValue;
                } else {
                    $returnValue[(int)$r][$c] = $cellValue;
                }
            }
        }

        return $returnValue;
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
