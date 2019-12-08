<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class SpanService
 * @package Hoogi91\Spreadsheets\Service
 */
class SpanService
{

    /**
     * @var array
     */
    private static $ignoredColumns = [];

    /**
     * @var array
     */
    private static $ignoredRows = [];

    /**
     * @var array
     */
    private static $ignoredCells = [];

    /**
     * @var array
     */
    private static $mergedCells = [];

    /**
     * @param Worksheet $worksheet
     * @return array
     * @throws SpreadsheetException
     */
    public function getIgnoredColumns(Worksheet $worksheet): array
    {
        $sheetHash = $this->getActiveSheetHashCode($worksheet->getParent(), $worksheet);
        if (isset(static::$ignoredColumns[$sheetHash])) {
            return static::$ignoredColumns[$sheetHash];
        }

        // get max. column count to identify ignored rows
        $maxRowCount = $worksheet->getHighestRow();
        if ($maxRowCount <= 0) {
            return [];
        }

        // map ignored cells by column
        $ignoredColumnsByRow = [];
        foreach ($this->getIgnoredCells($worksheet) as $value) {
            [$column, $row] = Coordinate::coordinateFromString($value);
            $ignoredColumnsByRow[$column][] = (int)$row;
        }

        // check if unique column values will exceed max. column count and should be ignored
        $ignoredColumnsByRow = array_map(static function ($values) use ($maxRowCount) {
            return count(array_unique($values)) >= $maxRowCount;
        }, $ignoredColumnsByRow);

        // only return row numbers of rows to ignore
        return static::$ignoredColumns[$sheetHash] = array_keys(array_filter($ignoredColumnsByRow));
    }

    /**
     * @param Worksheet $worksheet
     * @return array
     * @throws SpreadsheetException
     */
    public function getIgnoredRows(Worksheet $worksheet): array
    {
        $sheetHash = $this->getActiveSheetHashCode($worksheet->getParent(), $worksheet);
        if (isset(static::$ignoredRows[$sheetHash])) {
            return static::$ignoredRows[$sheetHash];
        }

        // get max. column count to identify ignored rows
        $maxColumnCount = $this->getActiveSheetColumnCount($worksheet);
        if ($maxColumnCount <= 0) {
            return [];
        }

        // map ignored cells by row
        $ignoredRowsByColumn = [];
        foreach ($this->getIgnoredCells($worksheet) as $value) {
            [$column, $row] = Coordinate::coordinateFromString($value);
            $ignoredRowsByColumn[(int)$row][] = $column;
        }

        // check if unique column values will exceed max. column count and should be ignored
        $ignoredRowsByColumn = array_map(static function ($values) use ($maxColumnCount) {
            return count(array_unique($values)) >= $maxColumnCount;
        }, $ignoredRowsByColumn);

        // only return row numbers of rows to ignore
        return static::$ignoredRows[$sheetHash] = array_keys(array_filter($ignoredRowsByColumn));
    }

    /**
     * @param Worksheet $worksheet
     * @return array
     */
    public function getIgnoredCells(Worksheet $worksheet): array
    {
        $sheetHash = $this->getActiveSheetHashCode($worksheet->getParent(), $worksheet);
        if (isset(static::$ignoredCells[$sheetHash])) {
            return static::$ignoredCells[$sheetHash];
        }

        $ignoredCells = [];
        /** @var string $cells */
        foreach ($worksheet->getMergeCells() as $cells) {
            // get all cell references by range
            $cellsByRange = Coordinate::extractAllCellReferencesInRange($cells);

            // shift off first cell / base cell
            array_shift($cellsByRange);

            // push all other cells to ignore them
            array_push($ignoredCells, ...$cellsByRange);
        }

        return static::$ignoredCells[$sheetHash] = array_unique($ignoredCells);
    }

    /**
     * @param Worksheet $worksheet
     * @return array
     * @throws SpreadsheetException
     */
    public function getMergedCells(Worksheet $worksheet): array
    {
        $sheetHash = $this->getActiveSheetHashCode($worksheet->getParent(), $worksheet);
        if (isset(static::$mergedCells[$sheetHash])) {
            return static::$mergedCells[$sheetHash];
        }

        $mergedCells = [];
        /** @var string $cells */
        foreach ($worksheet->getMergeCells() as $cells) {
            // get all cell references by range
            $cellsByRange = Coordinate::extractAllCellReferencesInRange($cells);
            $rangeDimension = $this->getRangeDimension(
                $cells,
                $this->getIgnoredColumns($worksheet),
                $this->getIgnoredRows($worksheet)
            );

            // shift off first cell / base cell
            $baseCell = array_shift($cellsByRange);

            // push base cell to merge cells with info about row- and colspan
            $mergedCells[$baseCell] = [
                'colspan' => (int)$rangeDimension[0],
                'rowspan' => (int)$rangeDimension[1],
                'additionalStyleIndexes' => $this->getCellStyleIndexesFromReferences($worksheet, $cellsByRange),
            ];
        }

        return static::$mergedCells[$sheetHash] = $mergedCells;
    }

    /**
     * @param Worksheet $worksheet
     * @param array $references
     *
     * @return array
     */
    private function getCellStyleIndexesFromReferences(Worksheet $worksheet, array $references): array
    {
        if (empty($references)) {
            return [];
        }

        try {
            $result = [];
            foreach ($references as $cellRef) {
                $cell = $worksheet->getCell($cellRef);
                if ($cell === null) {
                    continue;
                }

                $result[] = $cell->getXfIndex();
            }
            return array_unique($result);
        } catch (SpreadsheetException $e) {
            // return empty cell style information if spreadsheet couldn't be loaded
        }
        return [];
    }

    /**
     * @param string $range
     * @param array $ignoredRows
     * @param array $ignoredColumns
     * @return array
     */
    private function getRangeDimension(string $range, array $ignoredRows, array $ignoredColumns): array
    {
        if (empty($range) || Coordinate::coordinateIsRange($range) !== true) {
            return [];
        }

        // get default dimension and all cells inside given range
        $rangeDimension = Coordinate::rangeDimension($range);
        $cellsByRange = Coordinate::extractAllCellReferencesInRange($range);

        // get information about rows and cols in cell references
        $rowsInRange = array_unique(array_map(static function ($cell) {
            return (int)Coordinate::coordinateFromString($cell)[1];
        }, $cellsByRange));
        $columnsInRange = array_unique(array_map(static function ($cell) {
            return Coordinate::coordinateFromString($cell)[0];
        }, $cellsByRange));

        // update rangeDimension by count of ignoredRows inside rows in current range
        $rangeDimension[0] -= count(array_intersect($columnsInRange, $ignoredColumns));
        $rangeDimension[1] -= count(array_intersect($rowsInRange, $ignoredRows));

        return $rangeDimension;
    }

    /**
     * @param Worksheet $worksheet
     * @return int
     */
    private function getActiveSheetColumnCount(Worksheet $worksheet): int
    {
        try {
            return Coordinate::columnIndexFromString($worksheet->getHighestColumn());
        } catch (SpreadSheetException $e) {
            // return zero if active worksheet couldn't be loaded
            return 0;
        }
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param Worksheet $worksheet
     * @return string
     */
    private function getActiveSheetHashCode(Spreadsheet $spreadsheet, Worksheet $worksheet): string
    {
        return md5($spreadsheet->getID() . $worksheet->getHashCode());
    }
}
