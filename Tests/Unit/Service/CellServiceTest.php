<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\CellService;
use Hoogi91\Spreadsheets\Service\StyleService;
use Hoogi91\Spreadsheets\Service\ValueMappingService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CellServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class CellServiceTest extends UnitTestCase
{

    private const TEST_FORMATTING_SHEET_INDEX = 1;

    /**
     * @var CellService
     */
    private $cellService;

    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    /**
     * @throws SpreadsheetException
     */
    protected function setUp()
    {
        parent::setUp();
        $this->spreadsheet = (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');
        $this->cellService = new CellService(new StyleService(new ValueMappingService()));
    }

    public function testReadingOfCellValues(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);

        // assert data from value
        $this->assertEquals('2014', $this->cellService->getFormattedValue($worksheet->getCell('A1')));
        $this->assertEquals('©™§∆', $this->cellService->getFormattedValue($worksheet->getCell('A4')));
        $this->assertEquals('Test123', $this->cellService->getFormattedValue($worksheet->getCell('C4')));
        $this->assertEquals('Link', $this->cellService->getFormattedValue($worksheet->getCell('D4')));
        $this->assertEquals('Hoch', $this->cellService->getFormattedValue($worksheet->getCell('E5')));
        $this->assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $this->cellService->getFormattedValue($worksheet->getCell('D5'))
        );
    }

    public function testReadingOfRawCellValues(): void
    {
        $worksheet = $this->spreadsheet->getSheet(self::TEST_FORMATTING_SHEET_INDEX);

        // read raw values directly from cell without formatting
        $this->assertEquals('=A1+B1', $worksheet->getCell('B2')->getValue());
        $this->assertEquals('=A1-B1', $worksheet->getCell('B3')->getValue());
        $this->assertEquals('=A1*B1', $worksheet->getCell('B4')->getValue());
        $this->assertEquals('=B1/A1', $worksheet->getCell('B5')->getValue());
    }

    public function testReadingOfCalculatedCellValues(): void
    {
        $worksheet = $this->spreadsheet->getSheet(self::TEST_FORMATTING_SHEET_INDEX);

        // assert data from value
        $this->assertEquals(579.0, $this->cellService->getFormattedValue($worksheet->getCell('B2')));
        $this->assertEquals(-333, $this->cellService->getFormattedValue($worksheet->getCell('B3')));
        $this->assertEquals(56088, $this->cellService->getFormattedValue($worksheet->getCell('B4')));
        $this->assertEquals(3.7073170731707319, $this->cellService->getFormattedValue($worksheet->getCell('B5')));
    }

    public function testReadingOfCalculatedAndFormattedValues(): void
    {
        $worksheet = $this->spreadsheet->getSheet(self::TEST_FORMATTING_SHEET_INDEX);

        // assert data from value
        $this->assertEquals('5.8E+2', $this->cellService->getFormattedValue($worksheet->getCell('C2')));
        $this->assertEquals('5.790E+2', $this->cellService->getFormattedValue($worksheet->getCell('C9')));
        $this->assertEquals('-333.0 €', $this->cellService->getFormattedValue($worksheet->getCell('C3')));
        $this->assertEquals('56,088.000', $this->cellService->getFormattedValue($worksheet->getCell('C4')));
        $this->assertEquals('¥3.707', $this->cellService->getFormattedValue($worksheet->getCell('C5')));
        $this->assertEquals('3.7 ₽', $this->cellService->getFormattedValue($worksheet->getCell('C6')));
        $this->assertEquals('370.73%', $this->cellService->getFormattedValue($worksheet->getCell('C7')));

        // check date field calculated and complete formatted
        $this->assertEquals(43544.0, $worksheet->getCell('C8')->getCalculatedValue());
        $this->assertEquals(
            'Wednesday, March 20, 2019',
            $this->cellService->getFormattedValue($worksheet->getCell('C8'))
        );
    }

    public function testCatchingOfCalculatedCellValues(): void
    {
        // create cell mock to get test exception in cell service
        /** @var MockObject|Cell $mockedCell */
        $mockedCell = $this->getMockBuilder(Cell::class)->disableOriginalConstructor()->getMock();
        $mockedCell->method('getCalculatedValue')->willThrowException(new SpreadsheetException());
        $mockedCell->method('getValue')->willReturn('MockValue');

        // build chain for style index search
        $worksheetMock = $this->getMockBuilder(Worksheet::class)->disableOriginalConstructor()->getMock();
        $worksheetMock->method('getParent')->willReturn($this->spreadsheet);

        $mockedCell->method('getWorksheet')->willReturn($worksheetMock);

        $this->assertEquals('MockValue', $this->cellService->getFormattedValue($mockedCell));
    }
}
