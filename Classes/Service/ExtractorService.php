<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use Hoogi91\Spreadsheets\Domain\ValueObject;
use Iterator;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use PhpOffice\PhpSpreadsheet\Worksheet;

/**
 * Class ExtractorService
 * @package Hoogi91\Spreadsheets\Service
 */
class ExtractorService
{
    public const EXTRACT_DIRECTION_HORIZONTAL = 'horizontal';
    public const EXTRACT_DIRECTION_VERTICAL = 'vertical';

    /**
     * @var ReaderService
     */
    private $readerService;

    /**
     * @var CellService
     */
    private $cellService;

    /**
     * @var SpanService
     */
    private $spanService;

    /**
     * @var RangeService
     */
    private $rangeService;

    /**
     * ExtractorService constructor.
     *
     * @param ReaderService $readerService
     * @param CellService $cellService
     * @param SpanService $spanService
     * @param RangeService $rangeService
     */
    public function __construct(
        ReaderService $readerService,
        CellService $cellService,
        SpanService $spanService,
        RangeService $rangeService
    ) {
        $this->readerService = $readerService;
        $this->cellService = $cellService;
        $this->spanService = $spanService;
        $this->rangeService = $rangeService;
    }

    /**
     * @param ValueObject\DsnValueObject $dsnValue
     * @param bool $returnCellRef
     * @return ValueObject\ExtractionValueObject|null
     */
    public function getDataByDsnValueObject(ValueObject\DsnValueObject $dsnValue, bool $returnCellRef = false): ?ValueObject\ExtractionValueObject
    {
        try {
            $spreadsheet = $this->readerService->getSpreadsheet($dsnValue->getFileReference());
        } catch (SpreadsheetReaderException $e) {
            return null;
        }

        try {
            // calculate correct range from worksheet or selection
            $worksheet = $spreadsheet->setActiveSheetIndex($dsnValue->getSheetIndex());
            $range = $dsnValue->getSelection();
            if ($range === null) {
                $range = sprintf('A1:%s%d', $worksheet->getHighestColumn(), $worksheet->getHighestRow());
            }

            // get cell data and return value object
            $cellData = $this->rangeToCellArray(
                $worksheet,
                $range,
                $dsnValue->getDirectionOfSelection() ?? self::EXTRACT_DIRECTION_HORIZONTAL,
                $returnCellRef
            );

            return ValueObject\ExtractionValueObject::create($spreadsheet, $cellData);
        } catch (SpreadsheetException $exception) {
            return ValueObject\ExtractionValueObject::create($spreadsheet, []);
        }
    }

    /**
     * @param Worksheet\Worksheet $sheet
     * @param bool $returnCellRef
     * @return ValueObject\CellDataValueObject[][]
     */
    public function getHeadData(Worksheet\Worksheet $sheet, bool $returnCellRef = false): array
    {
        try {
            if ($sheet->getPageSetup()->isRowsToRepeatAtTopSet() === false) {
                return [];
            }

            $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();
            $range = 'A1:' . $sheet->getHighestColumn() . ($rowsToRepeatAtTop[1] + 1);
            return $this->rangeToCellArray($sheet, $range, self::EXTRACT_DIRECTION_HORIZONTAL, $returnCellRef);
        } catch (SpreadsheetException $e) {
            // sheet or range of cells couldn't be loaded
            return [];
        }
    }

    /**
     * @param Worksheet\Worksheet $sheet
     * @param bool $returnCellRef
     * @return ValueObject\CellDataValueObject[][]
     */
    public function getBodyData(Worksheet\Worksheet $sheet, bool $returnCellRef = false): array
    {
        try {
            if ($sheet->getPageSetup()->isRowsToRepeatAtTopSet() === false) {
                $range = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                return $this->rangeToCellArray($sheet, $range, self::EXTRACT_DIRECTION_HORIZONTAL, $returnCellRef);
            }

            $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();
            $range = 'A' . ($rowsToRepeatAtTop[1] + 1) . ':' . $sheet->getHighestColumn() . $sheet->getHighestRow();
            return $this->rangeToCellArray($sheet, $range, self::EXTRACT_DIRECTION_HORIZONTAL, $returnCellRef);
        } catch (SpreadsheetException $e) {
            // sheet or range of cells couldn't be loaded
            return [];
        }
    }

    /**
     * Create array from a range of cells.
     *
     * @param Worksheet\Worksheet $sheet
     * @param string $range Range of cells (i.e. "A1:B10"), or just one cell (i.e. "A1")
     * @param string $direction
     * @param bool $returnCellRef False - Return array of rows/columns indexed by number counting from zero
     *                                  True - Return rows and columns indexed by their actual row and column IDs
     *
     * @return ValueObject\CellDataValueObject[][]
     * @throws SpreadsheetException
     */
    public function rangeToCellArray(
        Worksheet\Worksheet $sheet,
        string $range,
        string $direction = self::EXTRACT_DIRECTION_HORIZONTAL,
        bool $returnCellRef = false
    ): array {
        // Identify the range that we need to extract from the worksheet
        [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($this->rangeService->convert($sheet, $range));

        // set ignored cells, cell iterator range and iterator type to use (column or row)
        if ($direction === self::EXTRACT_DIRECTION_VERTICAL) {
            $cellArray = $this->processIteratorCellsWithCallback(
                $sheet->getColumnIterator(
                    Coordinate::stringFromColumnIndex($rangeStart[0]),
                    Coordinate::stringFromColumnIndex($rangeEnd[0])
                ),
                [(int)$rangeStart[1], (int)$rangeEnd[1]],
                $this->spanService->getIgnoredColumns($sheet),
                $this->spanService->getIgnoredCells($sheet),
                $this->spanService->getMergedCells($sheet)
            );
        } else {
            $cellArray = $this->processIteratorCellsWithCallback(
                $sheet->getRowIterator((int)$rangeStart[1], (int)$rangeEnd[1]),
                [Coordinate::stringFromColumnIndex($rangeStart[0]), Coordinate::stringFromColumnIndex($rangeEnd[0])],
                $this->spanService->getIgnoredRows($sheet),
                $this->spanService->getIgnoredCells($sheet),
                $this->spanService->getMergedCells($sheet)
            );
        }

        if ($returnCellRef === true) {
            $cellArray = $this->updateColumnIndexesFromString($cellArray, $direction);
        }
        return $cellArray;
    }

    /**
     * @param Iterator $iterator
     * @param array $cellIteratorArgs
     * @param array $ignoredCellLines
     * @param array $ignoreCells
     * @param array $mergedCells
     *
     * @return ValueObject\CellDataValueObject[][]
     * @throws SpreadsheetException
     */
    private function processIteratorCellsWithCallback(
        Iterator $iterator,
        array $cellIteratorArgs = [],
        array $ignoredCellLines = [],
        array $ignoreCells = [],
        array $mergedCells = []
    ): array {
        $returnValue = [];
        foreach ($iterator as $line => $cells) {
            if (in_array($line, $ignoredCellLines, true)) {
                continue; // this row/column can be completely ignored
            }

            /** @var Worksheet\RowCellIterator|Worksheet\ColumnCellIterator $cellIterator */
            $cellIterator = $cells->getCellIterator(...$cellIteratorArgs);
            $cellIterator->setIterateOnlyExistingCells(false); // loop all cells ;)

            foreach ($cellIterator as $cellIndex => $cell) {
                $cellReference = $cellIterator instanceof Worksheet\ColumnCellIterator
                    ? ($line . $cellIndex)
                    : ($cellIndex . $line);

                if (in_array($cellReference, $ignoreCells, true)) {
                    continue; // ignore processing of this cell
                }

                $getCellArgs = [$cell, $mergedCells[$cellReference] ?? []];
                if ($cellIterator instanceof Worksheet\ColumnCellIterator) {
                    // column-based $line should be string and cellIndex is row integer
                    $returnValue[$line][(int)$cellIndex] = $this->getCellValue(...$getCellArgs);
                } else {
                    // row-based $line is integer and cellIndex should be column string
                    $returnValue[(int)$line][$cellIndex] = $this->getCellValue(...$getCellArgs);
                }
            }
        }
        return $returnValue;
    }

    /**
     * @param Cell $cell
     * @param array $mergeInformation
     *
     * @return ValueObject\CellDataValueObject
     * @throws SpreadsheetException
     */
    private function getCellValue(Cell $cell, array $mergeInformation = []): ValueObject\CellDataValueObject
    {
        return ValueObject\CellDataValueObject::create(
            $cell,
            $this->cellService->getFormattedValue($cell),
            (int)($mergeInformation['rowspan'] ?? 0),
            (int)($mergeInformation['colspan'] ?? 0),
            (array)($mergeInformation['additionalStyleIndexes'] ?? [])
        );
    }

    /**
     * @param ValueObject\CellDataValueObject[][] $cellArray
     * @param string $direction
     *
     * @return ValueObject\CellDataValueObject[][]
     */
    private function updateColumnIndexesFromString(
        array $cellArray,
        string $direction = self::EXTRACT_DIRECTION_HORIZONTAL
    ): array {
        if ($direction === self::EXTRACT_DIRECTION_VERTICAL) {
            // just get all keys and values for array combine
            // before combine map all keys to get column index from string
            return array_combine(
                array_map(
                    static function ($key) {
                        return Coordinate::columnIndexFromString($key);
                    },
                    array_keys($cellArray)
                ),
                array_values($cellArray)
            );
        }

        // iterate all rows and do same column conversion as above
        foreach ($cellArray as $row => $columns) {
            $cellArray[$row] = array_combine(
                array_map(
                    static function ($key) {
                        return Coordinate::columnIndexFromString($key);
                    },
                    array_keys($columns)
                ),
                array_values($columns)
            );
        }

        return $cellArray;
    }
}
