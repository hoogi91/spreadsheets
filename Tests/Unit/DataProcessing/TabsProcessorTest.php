<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\DataProcessing;

use Hoogi91\Spreadsheets\DataProcessing\AbstractProcessor;
use Hoogi91\Spreadsheets\DataProcessing\TabsProcessor;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class TabsProcessorTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\DataProcessing
 */
class TabsProcessorTest extends AbstractProcessorTest
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
     * @param MockObject|Spreadsheet $spreadsheetMock
     * @return void
     */
    protected function validInputExpectations(MockObject $spreadsheetMock): void
    {
        // mock worksheet will be returned
        $worksheetMock = $this->createConfiguredMock(
            Worksheet::class,
            [
                'getTitle' => 'Worksheet #1',
                'getHashCode' => '263df821f3760dc1ec4e'
            ]
        );
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

    public function processingDataProvider(): array
    {
        return [
            [
                // empty value should result in unprocessed input data
                ['value' => ''],
                self::INPUT_DATA,
            ],
            [
                // invalid value should also result in unprocessed input data
                ['value' => 'file:'],
                self::INPUT_DATA,
            ],
            [
                // result should contain a custom named variable with extraction result
                // page renderer will be checked if it is NOT being called
                [
                    'value' => 'file:123|1!A1:B2',
                    'options.' => [
                        'ignoreStyles' => 1,
                        'additionalStyles' => '.test{color: "#fff"}',
                    ],
                    'as' => 'someOtherVar',
                ],
                self::INPUT_DATA + [
                    'someOtherVar' => [
                        // key is file uid and hash code
                        '123263df821f3760dc1ec4e' => [
                            'sheetTitle' => 'Worksheet #1',
                            'bodyData' => ['body-data-mocked'],
                            'headData' => ['head-data-mocked'],
                        ],
                    ]
                ],
            ],
            [
                // result should contain default named result variable with extraction result
                // page renderer will be checked if it HAS being called
                [
                    'value' => 'file:123|2!A1:B2',
                    'options.' => ['additionalStyles' => '.test{color: "#fff"}',]
                ],
                self::INPUT_DATA + [
                    'spreadsheets' => [
                        // key is file uid and hash code
                        '123263df821f3760dc1ec4e' => [
                            'sheetTitle' => 'Worksheet #1',
                            'bodyData' => ['body-data-mocked'],
                            'headData' => ['head-data-mocked'],
                        ],
                    ]
                ],
            ],
        ];
    }
}
