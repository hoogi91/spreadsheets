<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\DataProcessing;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class TabsProcessor extends AbstractProcessor
{
    /**
     * @param DsnValueObject $dsn DSN which is processed
     * @param Spreadsheet $spreadsheet Spreadsheet that is processed
     * @param array<mixed> $processedData Processed data
     * @return array<mixed>
     */
    protected function getTemplateData(DsnValueObject $dsn, Spreadsheet $spreadsheet, array $processedData): array
    {
        $sheetData = [];
        foreach ($spreadsheet->getAllSheets() as $worksheet) {
            $sheetIdentifier = $dsn->getFileReference() . $worksheet->getHashCode();
            $sheetData[$sheetIdentifier] = [
                'sheetTitle' => $worksheet->getTitle(),
                'bodyData' => $this->getExtractorService()->getBodyData($worksheet, true),
                'headData' => $this->getExtractorService()->getHeadData($worksheet, true),
            ];
        }

        return $sheetData;
    }
}
