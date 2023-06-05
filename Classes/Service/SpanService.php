<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpanService
{
    /**
     * @var array<string, array<string>>
     */
    private static array $ignoredColumns = [];

    /**
     * @var array<string, array<int>>
     */
    private static array $ignoredRows = [];

    /**
     * @var array<string, array<string>>
     */
    private static array $ignoredCells = [];

    /**
     * @var array<string, array<int|string, array<string, int|array<int>>>>
     */
    private static array $mergedCells = [];

    /**
     * @return array<string>
     * @throws SpreadsheetException
     */
    public function getIgnoredColumns(Worksheet $worksheet): array
    {
        $sheetHash = $this->getActiveSheetHashCode($worksheet->getParent(), $worksheet);
        if (isset(self::$ignoredColumns[$sheetHash])) {
            return self::$ignoredColumns[$sheetHash];
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
            static fn ($values) => count(array_unique($values)) >= $maxRowCount,
            $ignoredColumnsByRow
        );

        // only return row numbers of rows to ignore
        return self::$ignoredColumns[$sheetHash] = array_keys(array_filter($ignoredColumnsByRow));
    }

    /**
     * @return array<int>
     * @throws SpreadsheetException
     */
    public function getIgnoredRows(Worksheet $worksheet): array
    {
        $sheetHash = $this->getActiveSheetHashCode($worksheet->getParent(), $worksheet);
        if (isset(self::$ignoredRows[$sheetHash])) {
            return self::$ignoredRows[$sheetHash];
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
            static fn ($values) => count(array_unique($values)) >= $maxColumnCount,
            $ignoredRowsByColumn
        );

        // only return row numbers of rows to ignore
        return self::$ignoredRows[$sheetHash] = array_keys(array_filter($ignoredRowsByColumn));
    }

    /**
     * @return array<string>
     */
    public function getIgnoredCells(Worksheet $worksheet): array
    {
        $sheetHash = $this->getActiveSheetHashCode($worksheet->getParent(), $worksheet);
        if (isset(self::$ignoredCells[$sheetHash])) {
            return self::$ignoredCells[$sheetHash];
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

        return self::$ignoredCells[$sheetHash] = array_unique($ignoredCells);
    }

    /**
     * @return array<int|string, array<string, int|array<int>>>
     * @throws SpreadsheetException
     */
    public function getMergedCells(Worksheet $worksheet): array
    {
        $sheetHash = $this->getActiveSheetHashCode($worksheet->getParent(), $worksheet);
        if (isset(self::$mergedCells[$sheetHash])) {
            return self::$mergedCells[$sheetHash];
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

        return self::$mergedCells[$sheetHash] = $mergedCells;
    }

    /**
     * @param array<array<int>|CellAddress|string> $references
     * @return array<int>
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

    private function getActiveSheetHashCode(?Spreadsheet $spreadsheet, Worksheet $worksheet): string
    {
        return md5($spreadsheet?->getID() . $worksheet->getHashCode());
    }
}
