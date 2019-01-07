<?php

namespace Hoogi91\Spreadsheets\Tests\Domain\Model;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Hoogi91\Spreadsheets\DataProcessing\SpreadsheetProcessor;
use Hoogi91\Spreadsheets\Domain\Model\CellValue;
use Hoogi91\Spreadsheets\Service\ExtractorService;

/**
 * Class SpreadsheetProcessorTest
 * @package Hoogi91\Spreadsheets\Tests\Domain\Model
 */
class SpreadsheetProcessorTest extends UnitTestCase
{

    /**
     * @var array
     */
    protected $defaultProcessedData = [
        'someVar' => 'data',
    ];

    /**
     * @var array
     */
    protected $expectedDefaultProcessedData = [
        'someVar'     => 'data',
        'spreadsheet' => [
            'sheetIndex' => 0,
            'headData'   => [],
        ],
    ];

    /**
     * @var array
     */
    protected $expectedTargetVariableProcessedData = [
        'someVar'      => 'data',
        'someOtherVar' => [
            'sheetIndex' => 1,
            'headData'   => [],
        ],
    ];

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * initialize with default XLSX fixture table and
     * simplified typoscript frontend controller object to habe locale_all configuration set
     *
     * @throws ReaderException
     */
    protected function setUp()
    {
        parent::setUp();

        // create xlsx reader and load default fixture spreadsheet
        $reader = new Xlsx();
        $this->spreadsheet = $reader->load(dirname(__DIR__, 2) . '/Fixtures/DataProcessor/01_fixture.xlsx');
    }

    /**
     * @test
     */
    public function testProcessingWithoutDatabaseValue()
    {
        $dataProcessor = $this->createDataProcessorMock();
        $result = $dataProcessor->process($this->createContentObjectRendererMock(), [], [
            'value' => '',
        ], $this->defaultProcessedData);

        $this->assertEquals($this->defaultProcessedData, $result);
    }

    /**
     * @test
     */
    public function testProcessingWithInvalidDatabaseValue()
    {
        // set sheet index for mock to -1 to get null object for extractor service
        $dataProcessor = $this->createDataProcessorMock(-1);
        $result = $dataProcessor->process($this->createContentObjectRendererMock(), [], [
            'value' => 'file:',
        ], $this->defaultProcessedData);

        $this->assertEquals($this->defaultProcessedData, $result);
    }

    /**
     * @test
     */
    public function testProcessingWithIgnoringStyles()
    {
        $dataProcessor = $this->createDataProcessorMock(1);
        $result = $dataProcessor->process($this->createContentObjectRendererMock(), [], [
            'value'    => 'file:123|1!A1:B2',
            'options.' => [
                'ignoreStyles' => 1,
            ],
            'as'       => 'someOtherVar',
        ], $this->defaultProcessedData);

        $this->assertArraySubset($this->expectedTargetVariableProcessedData, $result);

        // assertions on body data
        /** @var CellValue $cellValueA1 */
        $cellValueA1 = $result['someOtherVar']['bodyData'][1][1];
        $this->assertInstanceOf(CellValue::class, $cellValueA1);
        $this->assertEquals(DataType::TYPE_NUMERIC, $cellValueA1->getType());
        $this->assertEquals('2015.00', $cellValueA1->getCell()->getFormattedValue());
    }

    /**
     * @test
     */
    public function testProcessing()
    {
        $dataProcessor = $this->createDataProcessorMock();
        $result = $dataProcessor->process($this->createContentObjectRendererMock(), [], [
            'value' => 'file:123|0!A1:B2',
        ], $this->defaultProcessedData);

        $this->assertArraySubset($this->expectedDefaultProcessedData, $result);

        // assertions on body data
        /** @var CellValue $cellValueA1 */
        $cellValueA1 = $result['spreadsheet']['bodyData'][1][1];
        $this->assertInstanceOf(CellValue::class, $cellValueA1);
        $this->assertEquals(DataType::TYPE_NUMERIC, $cellValueA1->getType());
        $this->assertEquals('2014.00', $cellValueA1->getCell()->getFormattedValue());
    }

    /**
     * @param int $sheetIndex
     *
     * @return MockObject|SpreadsheetProcessor
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function createDataProcessorMock($sheetIndex = 0)
    {
        // create processor mock that will always return mocked extractor service
        $processor = $this->getMockBuilder(SpreadsheetProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtractorService'])
            ->getMock();

        if ($sheetIndex >= 0) {
            $extractorService = new ExtractorService($this->spreadsheet, $sheetIndex);
            $processor->method('getExtractorService')->willReturn($extractorService);
        } else {
            $processor->method('getExtractorService')->willReturn(null);
        }
        return $processor;
    }

    /**
     * @return MockObject|ContentObjectRenderer
     */
    protected function createContentObjectRendererMock()
    {
        $contentObjectRenderer = $this->getMockBuilder(ContentObjectRenderer::class)
            ->disableOriginalConstructor()
            ->setMethods(['stdWrapValue'])
            ->getMock();

        $contentObjectRenderer->method('stdWrapValue')->willReturnCallback(
            function ($key, array $config, $defaultValue = '') {
                if (isset($config[$key])) {
                    return $config[$key];
                }
                return $defaultValue;
            }
        );
        return $contentObjectRenderer;
    }
}
