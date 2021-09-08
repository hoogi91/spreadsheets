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

        // map ignored cells by column
        $ignoredColumnsByRow = [];
        foreach ($this->getIgnoredCells($worksheet) as $value) {
            [$column, $row] = Coordinate::coordinateFromString($value);
            $ignoredColumnsByRow[$column][] = (int)$row;
        }

        // check if unique column values will exceed max. column count and should be ignored
        $maxRowCount = $worksheet->getHighestRow();
        $ignoredColumnsByRow = array_map(
            static function ($values) use ($maxRowCount) {
                return count(array_unique($values)) >= $maxRowCount;
            },
            $ignoredColumnsByRow
        );

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

        // map ignored cells by row
        $ignoredRowsByColumn = [];
        foreach ($this->getIgnoredCells($worksheet) as $value) {
            [$column, $row] = Coordinate::coordinateFromString($value);
            $ignoredRowsByColumn[(int)$row][] = $column;
        }

        // check if unique column values will exceed max. column count and should be ignored
        $maxColumnCount = Coordinate::columnIndexFromString($worksheet->getHighestColumn());
        $ignoredRowsByColumn = array_map(
            static function ($values) use ($maxColumnCount) {
                return count(array_unique($values)) >= $maxColumnCount;
            },
            $ignoredRowsByColumn
        );

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

        $rowCount = $worksheet->getHighestRow();
        $columnCount = Coordinate::columnIndexFromString($worksheet->getHighestColumn());

        $mergedCells = [];
        foreach ($worksheet->getMergeCells() as $cells) {
            // get all cell references by range
            $cellsByRange = Coordinate::extractAllCellReferencesInRange($cells);
            [$colspan, $rowspan] = Coordinate::rangeDimension($cells);

            // shift off first cell / base cell
            $baseCell = array_shift($cellsByRange);

            // push base cell to merge cells with info about row- and colspan
            $mergedCells[$baseCell] = [
                'colspan' => $rowspan === $rowCount ? 1 : $colspan,
                'rowspan' => $colspan === $columnCount ? 1 : $rowspan,
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
        $result = [];
        foreach ($references as $cellRef) {
            if ($worksheet->cellExists($cellRef) === true) {
                $result[] = $worksheet->getCell($cellRef)->getXfIndex();
            }
        }
        return array_unique($result);
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
