<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Traits;

use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Trait SheetIndexTrait
 * @package Hoogi91\Spreadsheets\Traits
 */
trait SheetIndexTrait
{
    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @return Spreadsheet
     */
    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }

    /**
     * @param Spreadsheet $spreadsheet
     */
    public function setSpreadsheet(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
    }

    /**
     * @return int
     */
    public function getSheetIndex(): int
    {
        return $this->spreadsheet->getActiveSheetIndex();
    }

    /**
     * @param int $index
     *
     * @return self
     * @throws SpreadsheetException
     */
    public function setSheetIndex(int $index)
    {
        $this->spreadsheet->setActiveSheetIndex($index);
        return $this;
    }
}
