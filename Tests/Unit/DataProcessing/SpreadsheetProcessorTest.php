<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\DataProcessing;

use Hoogi91\Spreadsheets\DataProcessing\SpreadsheetProcessor;
use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use Hoogi91\Spreadsheets\Domain\ValueObject\ExtractionValueObject;
use Hoogi91\Spreadsheets\Service;
use Hoogi91\Spreadsheets\Tests\Unit\ArrayAssertTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer as CObjRenderer;

/**
 * Class SpreadsheetProcessorTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\DataProcessing
 */
class SpreadsheetProcessorTest extends UnitTestCase
{

    use ArrayAssertTrait;

    private const INPUT_DATA = [
        'someVar' => 'data',
    ];

    /**
     * @var CObjRenderer|MockObject
     */
    private $cObjRendererMock;

    /**
     * @var PageRenderer|MockObject
     */
    private $pageRendererMock;

    /**
     * @var SpreadsheetProcessor
     */
    private $dataProcessor;

    /**
     * @var Service\ExtractorService|MockObject
     */
    private $extractorService;

    protected function setUp(): void
    {
        parent::setUp();

        // add general TYPO3 mock objects
        $this->pageRendererMock = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->getMock();
        $this->cObjRendererMock = $this->getMockBuilder(CObjRenderer::class)->disableOriginalConstructor()->getMock();
        $this->cObjRendererMock->method('stdWrapValue')->willReturnCallback(
            static function ($key, array $config, $defaultValue = '') {
                return $config[$key] ?? $defaultValue;
            }
        );

        // create dependencies for spreadsheet processor
        $styleService = $this->createMock(Service\StyleService::class);
        $this->extractorService = $this->createMock(Service\ExtractorService::class);

        // instantiate spreadsheet processor
        $this->dataProcessor = new SpreadsheetProcessor(
            $this->extractorService,
            $styleService,
            $this->pageRendererMock
        );
    }

    /**
     * @param array $processConfig
     * @param array $expectedResult
     *
     * @dataProvider processingDataProvider
     */
    public function testProcessing(array $processConfig, array $expectedResult): void
    {
        // add page renderer expectation based on ignoreStyle option
        if (isset($processConfig['options.']['ignoreStyles']) && $processConfig['options.']['ignoreStyles'] === 1) {
            $this->pageRendererMock->expects(self::never())->method('addCssFile');
        } elseif (self::INPUT_DATA !== $expectedResult) {
            $this->pageRendererMock->expects(self::once())->method('addCssFile')->with($this->isType('string'));
        }

        $spreadsheetMock = $this->createMock(Spreadsheet::class);
        $this->extractorService->method('getDataByDsnValueObject')->willReturn(
            ExtractionValueObject::create($spreadsheetMock, ['body-data-mocked'], ['head-data-mocked'])
        );

        // execute processor
        $result = $this->dataProcessor->process(
            $this->cObjRendererMock,
            [],
            $processConfig,
            self::INPUT_DATA
        );
        self::assertEquals($expectedResult, $result);
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
                    ],
                    'as' => 'someOtherVar',
                ],
                self::INPUT_DATA + [
                    'someOtherVar' => [
                        'sheetIndex' => 1,
                        'bodyData' => ['body-data-mocked'],
                        'headData' => ['head-data-mocked'],
                    ]
                ],
            ],
            [
                // result should contain default named result variable with extraction result
                // page renderer will be checked if it HAS being called
                ['value' => 'file:123|2!A1:B2'],
                self::INPUT_DATA + [
                    'spreadsheet' => [
                        'sheetIndex' => 2,
                        'bodyData' => ['body-data-mocked'],
                        'headData' => ['head-data-mocked'],
                    ]
                ],
            ],
        ];
    }

    /**
     * @param array $result
     */
    private function validateBodyData(array $result): void
    {
        /** @var CellDataValueObject|null $cellValueA1 */
        $cellValueA1 = $result['bodyData'][1][1] ?? null;

        self::assertInstanceOf(CellDataValueObject::class, $cellValueA1);
        self::assertEquals(DataType::TYPE_NUMERIC, $cellValueA1->getDataType());
        self::assertEquals('2015.00', $cellValueA1->getFormattedValue());
    }
}
