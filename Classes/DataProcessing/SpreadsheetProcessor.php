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
     * @param array $processedData Processed data
     * @return array
     */
    protected function getTemplateData(DsnValueObject $dsn, Spreadsheet $spreadsheet, array $processedData): array
    {
        $extraction = $this->getExtractorService()->getDataByDsnValueObject($dsn, true);
        $headData = $extraction->getHeadData();
        $bodyData = $extraction->getBodyData();

        // check if first row of body data should be header
        // 0 = "no header" | 1 = "top" | 2 = "left"
        if (empty($headData) && (int)$processedData['data']['table_header_position'] === 1) {
            $headData[] = array_shift($bodyData);
        }

        // check if last row of body data should be footer
        if ((bool)($processedData['data']['table_tfoot'] ?? 0) === true) {
            $footData[] = array_pop($bodyData);
        }

        return [
            'sheetIndex' => $dsn->getSheetIndex(),
            'firstColumnIsHeader' => empty($headData) && (int)$processedData['data']['table_header_position'] === 2,
            'headData' => $headData,
            'bodyData' => $bodyData,
            'footData' => $footData ?? [],
        ];
    }
}
