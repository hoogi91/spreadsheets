<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Hoogi91\Spreadsheets\Traits\SheetIndexTrait;

/**
 * Class SpanService
 * @package Hoogi91\Spreadsheets\Service
 */
class SpanService
{
    use SheetIndexTrait;

    /**
     * @var StyleService
     */
    protected $styleService;

    /**
     * @var array
     */
    protected static $ignoredColumns = [];

    /**
     * @var array
     */
    protected static $ignoredRows = [];

    /**
     * @var array
     */
    protected static $ignoredCells = [];

    /**
     * @var array
     */
    protected static $mergedCells = [];


    /**
     * SpanService constructor.
     *
     * @param Spreadsheet $spreadsheet
     */
    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->setSpreadsheet($spreadsheet);
        $this->styleService = GeneralUtility::makeInstance(StyleService::class, $this->getSpreadsheet());
    }

    /**
     * @return array
     * @throws SpreadsheetException
     */
    public function getIgnoredColumns(): array
    {
        $sheetHash = $this->getActiveSheetHashCode();
        if (isset(static::$ignoredColumns[$sheetHash])) {
            return static::$ignoredColumns[$sheetHash];
        }

        // get max. column count to identify ignored rows
        $maxRowCount = $this->getActiveSheetRowCount();
        if ($maxRowCount <= 0) {
            return [];
        }

        // map ignored cells by column
        $ignoredColumnsByRow = [];
        foreach ($this->getIgnoredCells() as $value) {
            list($column, $row) = Coordinate::coordinateFromString($value);
            $ignoredColumnsByRow[$column][] = (int)$row;
        }

        // check if unique column values will exceed max. column count and should be ignored
        $ignoredColumnsByRow = array_map(function ($values) use ($maxRowCount) {
            return count(array_unique($values)) >= $maxRowCount;
        }, $ignoredColumnsByRow);

        // only return row numbers of rows to ignore
        return static::$ignoredColumns[$sheetHash] = array_keys(array_filter($ignoredColumnsByRow));
    }

    /**
     * @return array
     * @throws SpreadsheetException
     */
    public function getIgnoredRows(): array
    {
        $sheetHash = $this->getActiveSheetHashCode();
        if (isset(static::$ignoredRows[$sheetHash])) {
            return static::$ignoredRows[$sheetHash];
        }

        // get max. column count to identify ignored rows
        $maxColumnCount = $this->getActiveSheetColumnCount();
        if ($maxColumnCount <= 0) {
            return [];
        }

        // map ignored cells by row
        $ignoredRowsByColumn = [];
        foreach ($this->getIgnoredCells() as $value) {
            list($column, $row) = Coordinate::coordinateFromString($value);
            $ignoredRowsByColumn[(int)$row][] = $column;
        }

        // check if unique column values will exceed max. column count and should be ignored
        $ignoredRowsByColumn = array_map(function ($values) use ($maxColumnCount) {
            return count(array_unique($values)) >= $maxColumnCount;
        }, $ignoredRowsByColumn);

        // only return row numbers of rows to ignore
        return static::$ignoredRows[$sheetHash] = array_keys(array_filter($ignoredRowsByColumn));
    }

    /**
     * @return array
     * @throws SpreadsheetException
     */
    public function getIgnoredCells(): array
    {
        $sheetHash = $this->getActiveSheetHashCode();
        if (isset(static::$ignoredCells[$sheetHash])) {
            return static::$ignoredCells[$sheetHash];
        }

        $ignoredCells = [];
        foreach ($this->getActiveSheetMergeCells() as $cells) {
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
     * @return array
     * @throws SpreadsheetException
     */
    public function getMergedCells(): array
    {
        $sheetHash = $this->getActiveSheetHashCode();
        if (isset(static::$mergedCells[$sheetHash])) {
            return static::$mergedCells[$sheetHash];
        }

        $mergedCells = [];
        foreach ($this->getActiveSheetMergeCells() as $cells) {
            // get all cell references by range
            $cellsByRange = Coordinate::extractAllCellReferencesInRange($cells);
            $rangeDimension = $this->getRangeDimension($cells);

            // shift off first cell / base cell
            $baseCell = array_shift($cellsByRange);

            // push basecell to merge cells with info about row- and colspan
            $mergedCells[$baseCell] = [
                'colspan'                => (int)$rangeDimension[0],
                'rowspan'                => (int)$rangeDimension[1],
                'additionalStyleIndexes' => $this->styleService->getCellStyleIndexesFromReferences($cellsByRange),
            ];
        }

        return static::$mergedCells[$sheetHash] = $mergedCells;
    }

    /**
     * @param string $range
     *
     * @return array
     * @throws SpreadsheetException
     */
    protected function getRangeDimension($range): array
    {
        if (empty($range) || Coordinate::coordinateIsRange($range) !== true) {
            return [];
        }

        // we need to know about ignored columns and rows to calculate right col- and rowspans
        $ignoredColumns = $this->getIgnoredColumns();
        $ignoredRows = $this->getIgnoredRows();

        // get default dimension and all cells inside given range
        $rangeDimension = Coordinate::rangeDimension($range);
        $cellsByRange = Coordinate::extractAllCellReferencesInRange($range);

        // get information about rows and cols in cell references
        $rowsInRange = array_unique(array_map(function ($cell) {
            return (int)Coordinate::coordinateFromString($cell)[1];
        }, $cellsByRange));
        $columnsInRange = array_unique(array_map(function ($cell) {
            return Coordinate::coordinateFromString($cell)[0];
        }, $cellsByRange));

        // update rangeDimension by count of ignoredRows inside rows in current range
        $rangeDimension[0] -= count(array_intersect($columnsInRange, $ignoredColumns));
        $rangeDimension[1] -= count(array_intersect($rowsInRange, $ignoredRows));

        return $rangeDimension;
    }

    /**
     * @return int
     */
    protected function getActiveSheetColumnCount(): int
    {
        try {
            return Coordinate::columnIndexFromString($this->getSpreadsheet()->getActiveSheet()->getHighestColumn());
        } catch (SpreadSheetException $e) {
            // return zero if active worksheet couldn't be loaded
            return 0;
        }
    }

    /**
     * @return int
     */
    protected function getActiveSheetRowCount(): int
    {
        try {
            return (int)$this->getSpreadsheet()->getActiveSheet()->getHighestRow();
        } catch (SpreadSheetException $e) {
            // return zero if active worksheet couldn't be loaded
            return 0;
        }
    }

    /**
     * @return array
     */
    protected function getActiveSheetMergeCells(): array
    {
        try {
            // check if merge cells are available
            $mergeCells = $this->getSpreadsheet()->getActiveSheet()->getMergeCells();
            if (!empty($mergeCells)) {
                return $mergeCells;
            }
        } catch (SpreadSheetException $e) {
            // return empty array if active worksheet couldn't be loaded
        }
        return [];
    }

    /**
     * @return string
     * @throws SpreadsheetException
     */
    protected function getActiveSheetHashCode(): string
    {
        return md5($this->getSpreadsheet()->getID() . $this->getSpreadsheet()->getActiveSheet()->getHashCode());
    }
}
