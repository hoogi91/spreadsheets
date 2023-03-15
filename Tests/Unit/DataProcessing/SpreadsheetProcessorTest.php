<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\DataProcessing;

use Hoogi91\Spreadsheets\DataProcessing\AbstractProcessor;
use Hoogi91\Spreadsheets\DataProcessing\SpreadsheetProcessor;
use Hoogi91\Spreadsheets\Domain\ValueObject\ExtractionValueObject;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\MockObject\MockObject;

class SpreadsheetProcessorTest extends AbstractProcessorTest
{
    protected function getDataProcessor(): AbstractProcessor
    {
        return new SpreadsheetProcessor(
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
        $this->extractorService->expects(self::once())
            ->method('getDataByDsnValueObject')
            ->willReturn(
                ExtractionValueObject::create($spreadsheetMock, ['body-data-mocked'], ['head-data-mocked'])
            );
    }

    /**
     * @param MockObject&Spreadsheet $spreadsheetMock
     */
    protected function validInputExpectationsOnlyWithBody(MockObject $spreadsheetMock): void
    {
        $this->extractorService->expects(self::once())
            ->method('getDataByDsnValueObject')
            ->willReturn(
                ExtractionValueObject::create(
                    $spreadsheetMock,
                    [
                        ['body-data-mocked-row-1'],
                        ['body-data-mocked-row-2'],
                        ['body-data-mocked-row-3'],
                    ]
                )
            );
    }

    /**
     * @param MockObject&Spreadsheet $spreadsheetMock
     */
    protected function validInputExpectationsWithEmptyBody(MockObject $spreadsheetMock): void
    {
        $this->extractorService->expects(self::once())
            ->method('getDataByDsnValueObject')
            ->willReturn(ExtractionValueObject::create($spreadsheetMock, []));
    }

    protected function invalidInputExpectations(): void
    {
        $this->extractorService->expects(self::never())->method('getDataByDsnValueObject');
    }

    /**
     * @return array<string, mixed>
     */
    public function processingDataProvider(): array
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
                        'sheetIndex' => 1,
                        'bodyData' => ['body-data-mocked'],
                        'headData' => ['head-data-mocked'],
                        'footData' => [],
                        'firstColumnIsHeader' => false,
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
                        'sheetIndex' => 2,
                        'bodyData' => ['body-data-mocked'],
                        'headData' => ['head-data-mocked'],
                        'footData' => [],
                        'firstColumnIsHeader' => false,
                    ],
                ],
            ],
            'top head data is only extracted from body if we do not have extracted head data' => [
                'processConfig' => ['value' => 'file:123|2!A1:B2'],
                'processedData' => ['data' => ['table_header_position' => 1]],
                'expectedResult' => [
                    'spreadsheets' => [
                        'sheetIndex' => 2,
                        'bodyData' => ['body-data-mocked'],
                        'headData' => ['head-data-mocked'],
                        'footData' => [],
                        'firstColumnIsHeader' => false,
                    ],
                ],
            ],
            'left head data is not set if we do have extracted head data' => [
                'processConfig' => ['value' => 'file:123|2!A1:B2'],
                'processedData' => ['data' => ['table_header_position' => 2]],
                'expectedResult' => [
                    'spreadsheets' => [
                        'sheetIndex' => 2,
                        'bodyData' => ['body-data-mocked'],
                        'headData' => ['head-data-mocked'],
                        'footData' => [],
                        'firstColumnIsHeader' => false,
                    ],
                ],
            ],
            'top head and foot data can be extracted from body data' => [
                'processConfig' => ['value' => 'file:123|2!A1:B2',],
                'processedData' => ['data' => ['table_header_position' => 1, 'table_tfoot' => 1]],
                'expectedResult' => [
                    'spreadsheets' => [
                        'sheetIndex' => 2,
                        'bodyData' => [['body-data-mocked-row-2']],
                        'headData' => [['body-data-mocked-row-1']],
                        'footData' => [['body-data-mocked-row-3']],
                        'firstColumnIsHeader' => false,
                    ],
                ],
                'alternativeExpectation' => $this->validInputExpectationsOnlyWithBody(...),
            ],
            'left head data is set because we do not have extracted head data' => [
                'processConfig' => ['value' => 'file:123|2!A1:B2'],
                'processedData' => ['data' => ['table_header_position' => 2]],
                'expectedResult' => [
                    'spreadsheets' => [
                        'sheetIndex' => 2,
                        'bodyData' => [
                            ['body-data-mocked-row-1'],
                            ['body-data-mocked-row-2'],
                            ['body-data-mocked-row-3'],
                        ],
                        'headData' => [],
                        'footData' => [],
                        'firstColumnIsHeader' => true,
                    ],
                ],
                'alternativeExpectation' => $this->validInputExpectationsOnlyWithBody(...),
            ],
            'head and foot data are not filled incorrectly when no data is given' => [
                'processConfig' => ['value' => 'file:123|2!A1:B2'],
                'processedData' => ['data' => ['table_header_position' => 1, 'table_tfoot' => 1]],
                'expectedResult' => [
                    'spreadsheets' => [
                        'sheetIndex' => 2,
                        'bodyData' => [],
                        'headData' => [],
                        'footData' => [],
                        'firstColumnIsHeader' => false,
                    ],
                ],
                'alternativeExpectation' => $this->validInputExpectationsWithEmptyBody(...),
            ],
        ];
    }
}
