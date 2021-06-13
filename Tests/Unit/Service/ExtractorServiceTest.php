<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use Hoogi91\Spreadsheets\Service;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * Class ExtractorServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class ExtractorServiceTest extends UnitTestCase
{
    /**
     * @var Service\ExtractorService
     */
    private $extractorService;

    /**
     * @var MockObject|Service\ReaderService
     */
    private $readerService;

    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    protected function setUp(): void
    {
        parent::setUp();

        // setup reader mock instance
        $this->spreadsheet = (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');
        $this->readerService = $this->getMockBuilder(Service\ReaderService::class)->getMock();
        $this->readerService->method('getSpreadsheet')->willReturn($this->spreadsheet);

        $this->extractorService = new Service\ExtractorService(
            $this->readerService,
            new Service\CellService(new Service\StyleService(new Service\ValueMappingService())),
            new Service\SpanService(),
            new Service\RangeService(),
            new Service\ValueMappingService()
        );
    }

    public function testExtractionWithEmptyDSN(): void
    {
        $this->expectException(InvalidDataSourceNameException::class);
        $this->extractorService->getDataByDsnValueObject(DsnValueObject::createFromDSN(''));
    }

    public function testExtractionWithInvalidDSN(): void
    {
        $this->expectException(InvalidDataSourceNameException::class);
        $this->extractorService->getDataByDsnValueObject(DsnValueObject::createFromDSN('file:0|0'));
    }

    public function testExtractionOfDataByDsnValueObject(): void
    {
        /** @var MockObject|DsnValueObject $mockDsnValueObject */
        $mockDsnValueObject = $this->getMockBuilder(DsnValueObject::class)->disableOriginalConstructor()->getMock();
        $mockDsnValueObject->expects(self::once())->method('getSheetIndex')->willReturn(0);
        $mockDsnValueObject->expects(self::once())->method('getFileReference')->willReturn(
            $this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock()
        );

        // execute the extractor and expect data from sheet 0
        $this->extractorService->getDataByDsnValueObject($mockDsnValueObject);
    }

    public function testHeadDataExtraction(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        self::assertEmpty($this->extractorService->getHeadData($worksheet, true));
    }

    public function testBodyDataExtraction(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $bodyData = $this->extractorService->getBodyData($worksheet, true);

        self::assertIsArray($bodyData);
        self::assertCount(9, $bodyData);

        /** @var CellDataValueObject $cellValueA1 */
        $cellValueA1 = $bodyData[1][1];
        self::assertInstanceOf(CellDataValueObject::class, $cellValueA1);
        self::assertEquals('2014', $cellValueA1->getRenderedValue());


        /** @var CellDataValueObject $cellValueD5 */
        $cellValueD5 = $bodyData[5][4];
        self::assertInstanceOf(CellDataValueObject::class, $cellValueD5);
        self::assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $cellValueD5->getRenderedValue()
        );
    }

    /**
     * @param string $range
     * @param string $direction
     * @param bool $cellRef
     *
     * @dataProvider rangeExtractorDataProvider
     */
    public function testRangeExtractor(string $range, string $direction, bool $cellRef = false): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $data = $this->extractorService->rangeToCellArray($worksheet, $range, $direction, $cellRef);

        /** @var CellDataValueObject $cellValueA1 */
        if ($direction === Service\ExtractorService::EXTRACT_DIRECTION_HORIZONTAL) {
            $cellValueA1 = $cellRef === false ? $data[1]['B'] : $data[1][2];
        } else {
            $cellValueA1 = $cellRef === false ? $data['B'][1] : $data[2][1];
        }
        self::assertInstanceOf(CellDataValueObject::class, $cellValueA1);
        self::assertEquals('2015', $cellValueA1->getRenderedValue());

        /** @var CellDataValueObject $cellValueE5 */
        if ($direction === Service\ExtractorService::EXTRACT_DIRECTION_HORIZONTAL) {
            $cellValueE5 = $cellRef === false ? $data[5]['D'] : $data[5][4];
        } else {
            $cellValueE5 = $cellRef === false ? $data['D'][5] : $data[4][5];
        }
        self::assertInstanceOf(CellDataValueObject::class, $cellValueE5);
        self::assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $cellValueE5->getRenderedValue()
        );
    }

    public function rangeExtractorDataProvider(): array
    {
        return [
            ['A1:E7', Service\ExtractorService::EXTRACT_DIRECTION_HORIZONTAL],
            ['A1:E7', Service\ExtractorService::EXTRACT_DIRECTION_HORIZONTAL, true],
            ['A1:E7', Service\ExtractorService::EXTRACT_DIRECTION_VERTICAL],
            ['A1:E7', Service\ExtractorService::EXTRACT_DIRECTION_VERTICAL, true],
        ];
    }
}
