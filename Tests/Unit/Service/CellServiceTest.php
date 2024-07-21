<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\CellService;
use Hoogi91\Spreadsheets\Service\StyleService;
use Hoogi91\Spreadsheets\Service\ValueMappingService;
use Hoogi91\Spreadsheets\Tests\Unit\Typo3RequestTrait;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CellServiceTest extends UnitTestCase
{
    use Typo3RequestTrait;

    private const TEST_FORMATTING_SHEET_INDEX = 1;

    private Spreadsheet $spreadsheet;

    protected CellService $cellService;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setTypo3Request();
        $this->spreadsheet = (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');

        $mappingService = $this->createTestProxy(ValueMappingService::class);
        $styleService = $this->createTestProxy(StyleService::class, [$mappingService]);
        $this->cellService = new CellService($styleService);
    }

    public function testReadingOfCellValues(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);

        // assert data from value
        self::assertEquals('2014', $this->cellService->getFormattedValue($worksheet->getCell('A1')));
        self::assertEquals('©™§∆', $this->cellService->getFormattedValue($worksheet->getCell('A4')));
        self::assertEquals('Test123', $this->cellService->getFormattedValue($worksheet->getCell('C4')));
        self::assertEquals('Link', $this->cellService->getFormattedValue($worksheet->getCell('D4')));
        self::assertEquals('Hoch', $this->cellService->getFormattedValue($worksheet->getCell('E5')));
        self::assertEquals('2018', $this->cellService->getFormattedValue($worksheet->getCell('E6')));
        self::assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span>' .
            '<span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $this->cellService->getFormattedValue($worksheet->getCell('D5'))
        );
    }

    public function testReadingOfRawCellValues(): void
    {
        $worksheet = $this->spreadsheet->getSheet(self::TEST_FORMATTING_SHEET_INDEX);

        // read raw values directly from cell without formatting
        self::assertEquals('=A1+B1', $worksheet->getCell('B2')->getValue());
        self::assertEquals('=A1-B1', $worksheet->getCell('B3')->getValue());
        self::assertEquals('=A1*B1', $worksheet->getCell('B4')->getValue());
        self::assertEquals('=B1/A1', $worksheet->getCell('B5')->getValue());
    }

    public function testReadingOfCalculatedCellValues(): void
    {
        $worksheet = $this->spreadsheet->getSheet(self::TEST_FORMATTING_SHEET_INDEX);

        // assert data from value
        self::assertEquals(579.0, $this->cellService->getFormattedValue($worksheet->getCell('B2')));
        self::assertEquals(-333, $this->cellService->getFormattedValue($worksheet->getCell('B3')));
        self::assertEquals(56_088, $this->cellService->getFormattedValue($worksheet->getCell('B4')));
        self::assertEquals(3.707_317_073_170_7, $this->cellService->getFormattedValue($worksheet->getCell('B5')));
    }

    public function testReadingOfCalculatedAndFormattedValues(): void
    {
        $worksheet = $this->spreadsheet->getSheet(self::TEST_FORMATTING_SHEET_INDEX);

        // assert data from value
        self::assertEquals('5.8E+2', $this->cellService->getFormattedValue($worksheet->getCell('C2')));
        self::assertEquals('5.790E+2', $this->cellService->getFormattedValue($worksheet->getCell('C9')));
        self::assertEquals('-333.0 €', $this->cellService->getFormattedValue($worksheet->getCell('C3')));
        self::assertEquals('56,088.000', $this->cellService->getFormattedValue($worksheet->getCell('C4')));
        self::assertEquals('¥3.707', $this->cellService->getFormattedValue($worksheet->getCell('C5')));
        self::assertEquals('3.7 ₽', $this->cellService->getFormattedValue($worksheet->getCell('C6')));
        self::assertEquals('370.73%', $this->cellService->getFormattedValue($worksheet->getCell('C7')));

        // check date field calculated and complete formatted
        self::assertEquals(43_544.0, $worksheet->getCell('C8')->getCalculatedValue());
        self::assertEquals(
            'Wednesday, March 20, 2019',
            $this->cellService->getFormattedValue($worksheet->getCell('C8'))
        );
    }

    public function testCatchingOfCalculatedCellValues(): void
    {
        // create cell mock to get test exception in cell service
        /** @var MockObject&Cell $mockedCell */
        $mockedCell = $this->getMockBuilder(Cell::class)->disableOriginalConstructor()->getMock();
        $mockedCell->method('getCalculatedValue')->willThrowException(new SpreadsheetException());
        $mockedCell->method('getValue')->willReturn('MockValue');

        // build chain for style index search
        $worksheetMock = new Worksheet($this->spreadsheet, 'Worksheet');
        $mockedCell->method('getWorksheet')->willReturn($worksheetMock);

        self::assertEquals('MockValue', $this->cellService->getFormattedValue($mockedCell));
    }

    public function testFormatRichTextCell(): void
    {
        $richText = new RichText();
        $richText->createText('my-text');
        $richText->createTextRun('another-text');

        $customRun = new Run('third-text');
        $customRun->setFont(null);
        $richText->addText($customRun);

        $cell = new Cell($richText, null, $this->spreadsheet->getActiveSheet());

        self::assertEquals(DataType::TYPE_INLINE, $cell->getDataType());
        self::assertEquals(
            'my-text<span style="color:#000000">another-text</span>third-text',
            $this->cellService->getFormattedValue($cell)
        );
    }
}
