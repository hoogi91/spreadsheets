<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\ValueObject;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class ExtractionValueObject
 * @package Hoogi91\Spreadsheets\Domain\ValueObject
 */
class ExtractionValueObject
{

    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    /**
     * @var CellDataValueObject[][]
     */
    private $bodyData;

    /**
     * @var CellDataValueObject[][]
     */
    private $headData;

    public function __construct(Spreadsheet $spreadsheet, array $bodyData, array $headData = [])
    {
        $this->spreadsheet = $spreadsheet;
        $this->bodyData = $bodyData;
        $this->headData = $headData;
    }

    public static function create(Spreadsheet $spreadsheet, array $bodyData, array $headData = []): ExtractionValueObject
    {
        return new self($spreadsheet, $bodyData, $headData);
    }

    /**
     * @return Spreadsheet
     */
    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }

    /**
     * @return CellDataValueObject[][]
     */
    public function getHeadData(): array
    {
        return $this->headData;
    }

    /**
     * @return CellDataValueObject[][]
     */
    public function getBodyData(): array
    {
        return $this->bodyData;
    }
}