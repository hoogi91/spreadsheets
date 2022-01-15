<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\DataProcessing;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class SpreadsheetProcessor
 * @package Hoogi91\Spreadsheets\DataProcessing
 */
class SpreadsheetProcessor extends AbstractProcessor
{

    /**
     * Get template relevant data
     * @param DsnValueObject $dsn DSN which is processed
     * @param Spreadsheet $spreadsheet Spreadsheet that is processed
     * @return array
     */
    protected function getTemplateData(DsnValueObject $dsn, Spreadsheet $spreadsheet): array
    {
        $extraction = $this->getExtractorService()->getDataByDsnValueObject($dsn, true);

        return [
            'sheetIndex' => $dsn->getSheetIndex(),
            'headData' => $extraction->getHeadData(),
            'bodyData' => $extraction->getBodyData(),
        ];
    }
}
