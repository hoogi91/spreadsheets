<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\DataProcessing;

use Hoogi91\Spreadsheets\DataProcessing\AbstractProcessor;
use Hoogi91\Spreadsheets\DataProcessing\TabsProcessor;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\MockObject\MockObject;

class TabsProcessorTest extends AbstractProcessorTestCase
{
    protected function getDataProcessor(): AbstractProcessor
    {
        return new TabsProcessor(
            $this->readerService,
            $this->extractorService,
            $this->styleService,
            $this->fileRepository,
            $this->pageRendererMock
        );
    }

    /**
     * @param MockObject&Spreadsheet $spreadsheetMock
     */
    protected function validInputExpectations(MockObject $spreadsheetMock): void
    {
        // mock worksheet will be returned
        $worksheetMock = $this->createMock(Worksheet::class);
        $worksheetMock->method('getTitle')->willReturn('Worksheet #1');
        $worksheetMock->method('getHashInt')->willReturn(2047);
        $spreadsheetMock->expects(self::once())->method('getAllSheets')->willReturn([$worksheetMock]);

        // check if extract gets called
        $this->extractorService->expects(self::once())
            ->method('getBodyData')
            ->with($worksheetMock, true)
            ->willReturn(['body-data-mocked']);
        $this->extractorService->expects(self::once())
            ->method('getHeadData')
            ->with($worksheetMock, true)
            ->willReturn(['head-data-mocked']);
    }

    protected function invalidInputExpectations(): void
    {
        $this->extractorService->expects(self::never())->method('getBodyData');
        $this->extractorService->expects(self::never())->method('getHeadData');
    }

    /**
     * @return array<string, mixed>
     */
    public static function processingDataProvider(): array
    {
        return [
            'empty value should result in unprocessed input data' => [
                'processConfig' => ['value' => ''],
            ],
            'invalid value should also result in unprocessed input data' => [
                'processConfig' => ['value' => 'file:'],
            ],
            'custom named variable and page renderer is not called' => [
                'processConfig' => [
                    'value' => 'file:123|1!A1:B2',
                    'options.' => [
                        'ignoreStyles' => 1,
                        'additionalStyles' => '.test{color: "#fff"}',
                    ],
                    'as' => 'someOtherVar',
                ],
                'processedData' => [],
                'expectedResult' => [
                    'someOtherVar' => [
                        // key is file uid and hash code
                        '1232047' => [
                            'sheetTitle' => 'Worksheet #1',
                            'bodyData' => ['body-data-mocked'],
                            'headData' => ['head-data-mocked'],
                        ],
                    ],
                ],
            ],
            'default named variable and page renderer has been called' => [
                'processConfig' => [
                    'value' => 'file:123|2!A1:B2',
                    'options.' => ['additionalStyles' => '.test{color: "#fff"}',],
                ],
                'processedData' => [],
                'expectedResult' => [
                    'spreadsheets' => [
                        // key is file uid and hash code
                        '1232047' => [
                            'sheetTitle' => 'Worksheet #1',
                            'bodyData' => ['body-data-mocked'],
                            'headData' => ['head-data-mocked'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
