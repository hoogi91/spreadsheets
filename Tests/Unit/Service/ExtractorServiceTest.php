<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use Hoogi91\Spreadsheets\Service;
use Hoogi91\Spreadsheets\Service\ExtractorService;
use Hoogi91\Spreadsheets\Tests\Unit\FileRepositoryMockTrait;
use Hoogi91\Spreadsheets\Tests\Unit\TsfeSetupTrait;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExtractorServiceTest extends UnitTestCase
{
    use FileRepositoryMockTrait;
    use TsfeSetupTrait;

    private ExtractorService $extractorService;

    private Spreadsheet $spreadsheet;

    /**
     * @var Service\SpanService&MockObject
     */
    private MockObject $spanService;

    protected function setUp(): void
    {
        parent::setUp();
        self::setupDefaultTSFE();

        // mock backend request mode
        $GLOBALS['TYPO3_REQUEST'] = $this->createConfiguredMock(
            ServerRequestInterface::class,
            ['getAttribute' => SystemEnvironmentBuilder::REQUESTTYPE_BE]
        );

        // setup reader mock instance
        $this->spreadsheet = (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');
        $readerService = $this->getMockBuilder(Service\ReaderService::class)->getMock();
        $readerService->method('getSpreadsheet')->willReturn($this->spreadsheet);

        $mappingService = $this->createTestProxy(Service\ValueMappingService::class);
        $styleService = $this->createTestProxy(Service\StyleService::class, [$mappingService]);
        $cellService = $this->createTestProxy(
            Service\CellService::class,
            [$styleService, $this->createMock(SiteFinder::class), $this->createMock(Context::class)]
        );
        $this->spanService = $this->createTestProxy(Service\SpanService::class);
        $this->extractorService = new Service\ExtractorService(
            $readerService,
            $cellService,
            $this->spanService,
            $this->createTestProxy(Service\RangeService::class),
            $mappingService,
            $this->getFileRepositoryMock()
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

    public function testExtractionWithExceptionHandling(): void
    {
        /** @var MockObject&DsnValueObject $mockDsnValueObject */
        $mockDsnValueObject = $this->getMockBuilder(DsnValueObject::class)->disableOriginalConstructor()->getMock();
        $mockDsnValueObject->expects(self::once())->method('getSheetIndex')->willReturn(0);
        $mockDsnValueObject->expects(self::once())->method('getFileReference')->willReturn(456);
        $mockDsnValueObject->expects(self::once())->method('getSelection')->willReturn('A1:B5');

        $this->spanService->method('getIgnoredRows')->willThrowException(new SpreadsheetException());
        $result = $this->extractorService->getDataByDsnValueObject($mockDsnValueObject);
        self::assertSame($this->spreadsheet, $result->getSpreadsheet());
        self::assertEmpty($result->getHeadData());
        self::assertEmpty($result->getBodyData());
    }

    public function testExtractionOfDataByDsnValueObject(): void
    {
        /** @var MockObject&DsnValueObject $mockDsnValueObject */
        $mockDsnValueObject = $this->getMockBuilder(DsnValueObject::class)->disableOriginalConstructor()->getMock();
        $mockDsnValueObject->expects(self::once())->method('getSheetIndex')->willReturn(0);
        $mockDsnValueObject->expects(self::once())->method('getFileReference')->willReturn(456);

        $result = $this->extractorService->getDataByDsnValueObject($mockDsnValueObject);
        self::assertSame($this->spreadsheet, $result->getSpreadsheet());
        self::assertEmpty($result->getHeadData());
        self::assertCount(10, $result->getBodyData());
    }

    public function testExtractionOfDataByDsnValueObjectWithRange(): void
    {
        /** @var MockObject&DsnValueObject $mockDsnValueObject */
        $mockDsnValueObject = $this->getMockBuilder(DsnValueObject::class)->disableOriginalConstructor()->getMock();
        $mockDsnValueObject->expects(self::once())->method('getSheetIndex')->willReturn(0);
        $mockDsnValueObject->expects(self::once())->method('getFileReference')->willReturn(456);
        $mockDsnValueObject->expects(self::once())->method('getSelection')->willReturn('A1:B5');

        // execute the extractor and expect data from sheet 0
        $result = $this->extractorService->getDataByDsnValueObject($mockDsnValueObject);
        self::assertSame($this->spreadsheet, $result->getSpreadsheet());
        self::assertEmpty($result->getHeadData());
        self::assertCount(5, $result->getBodyData());
    }

    public function testHeadDataExtraction(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $worksheet->getPageSetup()->setRowsToRepeatAtTop([1, 2]); // first two rows
        $headData = $this->extractorService->getHeadData($worksheet, true);

        self::assertIsArray($headData);
        self::assertCount(2, $headData);

        /** @var CellDataValueObject $cellValueA1 */
        $cellValueA1 = $headData[1][1];
        self::assertInstanceOf(CellDataValueObject::class, $cellValueA1);
        self::assertEquals('2014', $cellValueA1->getRenderedValue());
    }

    public function testHeadDataExtractionOnException(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $worksheet->getPageSetup()->setRowsToRepeatAtTop([1, 2]); // first two rows

        $this->spanService->method('getIgnoredRows')->willThrowException(new SpreadsheetException());
        self::assertEmpty($this->extractorService->getHeadData($worksheet, true));
    }

    public function testBodyDataExtraction(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $bodyData = $this->extractorService->getBodyData($worksheet, true);

        self::assertIsArray($bodyData);
        self::assertCount(10, $bodyData);

        /** @var CellDataValueObject $cellValueA1 */
        $cellValueA1 = $bodyData[1][1];
        self::assertInstanceOf(CellDataValueObject::class, $cellValueA1);
        self::assertEquals('2014', $cellValueA1->getRenderedValue());


        /** @var CellDataValueObject $cellValueD5 */
        $cellValueD5 = $bodyData[5][4];
        self::assertInstanceOf(CellDataValueObject::class, $cellValueD5);
        self::assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span>' .
            '<span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $cellValueD5->getRenderedValue()
        );
    }

    public function testBodyDataExtractionOnException(): void
    {
        $this->spanService->method('getIgnoredRows')->willThrowException(new SpreadsheetException());
        self::assertEmpty($this->extractorService->getBodyData($this->spreadsheet->getSheet(0), true));
    }

    /**
     * @dataProvider rangeExtractorDataProvider
     */
    public function testRangeExtractor(string $range, string $direction, bool $cellRef = false): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $data = $this->extractorService->rangeToCellArray($worksheet, $range, $direction, $cellRef);

        if ($direction === Service\ExtractorService::EXTRACT_DIRECTION_HORIZONTAL) {
            $cellValueA1 = $cellRef === false ? $data[1]['B'] : $data[1][2];
        } else {
            $cellValueA1 = $cellRef === false ? $data['B'][1] : $data[2][1];
        }
        /** @var CellDataValueObject $cellValueA1 */
        self::assertInstanceOf(CellDataValueObject::class, $cellValueA1);
        self::assertEquals('2015', $cellValueA1->getRenderedValue());

        if ($direction === Service\ExtractorService::EXTRACT_DIRECTION_HORIZONTAL) {
            $cellValueE5 = $cellRef === false ? $data[5]['D'] : $data[5][4];
        } else {
            $cellValueE5 = $cellRef === false ? $data['D'][5] : $data[4][5];
        }
        /** @var CellDataValueObject $cellValueE5 */
        self::assertInstanceOf(CellDataValueObject::class, $cellValueE5);
        self::assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span>' .
            '<span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $cellValueE5->getRenderedValue()
        );
    }

    /**
     * @return array<int, array<string|bool>>
     */
    public static function rangeExtractorDataProvider(): array
    {
        return [
            ['A1:E7', Service\ExtractorService::EXTRACT_DIRECTION_HORIZONTAL],
            ['A1:E7', Service\ExtractorService::EXTRACT_DIRECTION_HORIZONTAL, true],
            ['A1:E7', Service\ExtractorService::EXTRACT_DIRECTION_VERTICAL],
            ['A1:E7', Service\ExtractorService::EXTRACT_DIRECTION_VERTICAL, true],
        ];
    }

    public function testExtractingWithFixedRowsOnTop(): void
    {
        $worksheet = $this->spreadsheet->getActiveSheet();
        $worksheet->getPageSetup()->setRowsToRepeatAtTop([1, 2]); // first two rows

        $headData = $this->extractorService->getHeadData($worksheet);
        $bodyData = $this->extractorService->getBodyData($worksheet);

        self::assertIsArray($headData, 'Head data is not an array');
        self::assertIsArray($bodyData, 'Body data is not an array');
        self::assertCount(2, $headData);
        self::assertCount(8, $bodyData);

        /** @var CellDataValueObject $cellValueA1 */
        $cellValueA1 = $headData[1]['A'];
        self::assertInstanceOf(CellDataValueObject::class, $cellValueA1);
        self::assertEquals('2014', $cellValueA1->getRenderedValue());

        /** @var CellDataValueObject $cellValueC3 */
        $cellValueC3 = $headData[2]['C'];
        self::assertInstanceOf(CellDataValueObject::class, $cellValueC3);
        self::assertEquals('70', $cellValueC3->getRenderedValue());

        /** @var CellDataValueObject $cellValueG3 */
        $cellValueG3 = $bodyData[3]['G'];
        self::assertInstanceOf(CellDataValueObject::class, $cellValueG3);
        self::assertEquals('x', $cellValueG3->getRenderedValue());

        /** @var CellDataValueObject $cellValueA4 */
        $cellValueA4 = $bodyData[4]['A'];
        self::assertInstanceOf(CellDataValueObject::class, $cellValueA4);
        self::assertEquals('©™§∆', $cellValueA4->getRenderedValue());
    }
}
