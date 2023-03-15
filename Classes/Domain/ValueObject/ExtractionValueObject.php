<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\ValueObject;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExtractionValueObject
{
    /**
     * @param array<mixed> $bodyData
     * @param array<mixed> $headData
     */
    public function __construct(
        private readonly Spreadsheet $spreadsheet,
        private readonly array $bodyData,
        private readonly array $headData = []
    ) {
    }

    /**
     * @param array<mixed> $bodyData
     * @param array<mixed> $headData
     */
    public static function create(
        Spreadsheet $spreadsheet,
        array $bodyData,
        array $headData = []
    ): ExtractionValueObject {
        return new self($spreadsheet, $bodyData, $headData);
    }

    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }

    /**
     * @return array<mixed>
     */
    public function getHeadData(): array
    {
        return $this->headData;
    }

    /**
     * @return array<mixed>
     */
    public function getBodyData(): array
    {
        return $this->bodyData;
    }
}
