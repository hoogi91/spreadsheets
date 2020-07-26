<?php

namespace Hoogi91\Spreadsheets\Tests\Domain\Model;

use Hoogi91\Spreadsheets\DataProcessing\SpreadsheetProcessor;
use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use Hoogi91\Spreadsheets\Service;
use Hoogi91\Spreadsheets\Tests\Unit\ArrayAssertTrait;
use Hoogi91\Spreadsheets\Tests\Unit\FileRepositoryMockTrait;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer as CObjRenderer;

/**
 * Class SpreadsheetProcessorTest
 * @package Hoogi91\Spreadsheets\Tests\Domain\Model
 */
class SpreadsheetProcessorTest extends UnitTestCase
{

    use ArrayAssertTrait;
    use FileRepositoryMockTrait;

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
     * @throws ReaderException
     */
    protected function setUp()
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

        // add generic file repository mock to container
        /** @var ContainerInterface|MockObject $container */
        $container = $this->getContainerMock();
        $container->expects($this->any())->method('get')->willReturn($this->getFileRepositoryMock());
        GeneralUtility::setContainer($container);

        /** @var Service\ReaderService|MockObject $readerService */
        $readerService = $this->getMockBuilder(Service\ReaderService::class)->getMock();
        $readerService->method('getSpreadsheet')->willReturn(
            (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/DataProcessor/01_fixture.xlsx')
        );

        $GLOBALS['TSFE']->config['config']['locale_all'] = 'de';

        // create dependencies for spreadsheet processor
        $styleService = new Service\StyleService(new Service\ValueMappingService());
        $extractorService = new Service\ExtractorService(
            $readerService,
            new Service\CellService($styleService),
            new Service\SpanService(),
            new Service\RangeService(),
            new Service\ValueMappingService()
        );

        // instantiate spreadsheet processor
        $this->dataProcessor = new SpreadsheetProcessor($extractorService, $styleService, $this->pageRendererMock);
    }

    /**
     * @param array $processConfig
     * @param array $expectedResult
     * @param bool $subsetCheck
     * @param callable|null $callback
     *
     * @dataProvider processingDataProvider
     */
    public function testProcessing(
        array $processConfig,
        array $expectedResult,
        bool $subsetCheck = false,
        ?callable $callback = null
    ): void {
        // add page renderer expectation based on ignoreStyle option
        if (isset($processConfig['options.']['ignoreStyles']) && $processConfig['options.']['ignoreStyles'] === 1) {
            $this->pageRendererMock->expects($this->never())->method('addCssFile');
        } elseif (self::INPUT_DATA !== $expectedResult) {
            $this->pageRendererMock->expects($this->once())->method('addCssFile')->with($this->isType('string'));
        }

        // execute processor
        $result = $this->dataProcessor->process(
            $this->cObjRendererMock,
            [],
            $processConfig,
            self::INPUT_DATA
        );

        // check subset or equal arrays
        if ($subsetCheck === true) {
            self::assertArraySubsetWithoutClassInstances($expectedResult, $result, [CellDataValueObject::class]);
        } else {
            self::assertEquals($expectedResult, $result);
        }

        // execute additional checks in callback
        if ($callback !== null) {
            $callback($result[($processConfig['as'] ?? 'spreadsheet')] ?? []);
        }
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
                self::INPUT_DATA + ['someOtherVar' => ['sheetIndex' => 1, 'headData' => []]],
                true,
                [$this, 'validateBodyData'],
            ],
            [
                // result should contain default named result variable with extraction result
                // page renderer will be checked if it HAS being called
                ['value' => 'file:123|1!A1:B2'],
                self::INPUT_DATA + ['spreadsheet' => ['sheetIndex' => 1, 'headData' => []]],
                true,
                [$this, 'validateBodyData'],
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
