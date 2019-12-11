<?php

namespace Hoogi91\Spreadsheets\Tests\Domain\Model;

use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use Hoogi91\Spreadsheets\Service\CellService;
use Hoogi91\Spreadsheets\Service\StyleService;
use Hoogi91\Spreadsheets\Service\ValueMappingService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class CellValueTest
 * @package Hoogi91\Spreadsheets\Tests\Domain\Model
 */
class CellValueTest extends UnitTestCase
{

    /**
     * @var Worksheet
     */
    protected $sheet;

    /**
     * @var CellService
     */
    protected $cellService;

    /**
     * @throws SpreadsheetException
     * @throws SpreadsheetReaderException
     */
    protected function setUp()
    {
        parent::setUp();

        $sheet = (new Xlsx())->load(dirname(__DIR__, 3) . '/Fixtures/01_fixture.xlsx');
        $this->sheet = $sheet->getSheet(0);

        $this->cellService = new CellService(new StyleService(new ValueMappingService()));
    }

    /**
     * @throws SpreadsheetException
     */
    public function testDefaultCell()
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('A1'),
            $this->cellService->getFormattedValue($this->sheet->getCell('A1'))
        );

        // assert data from value
        $this->assertEquals('2014', $cellValue->getCalculatedValue());
        $this->assertEquals(DataType::TYPE_NUMERIC, $cellValue->getType());
        $this->assertFalse($cellValue->isRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }

    /**
     * @throws SpreadsheetException
     */
    public function testSpecialCharsCell()
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('A4'),
            $this->cellService->getFormattedValue($this->sheet->getCell('A4'))
        );

        // assert data from value
        $this->assertEquals('©™§∆', $cellValue->getCalculatedValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertFalse($cellValue->isRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }

    /**
     * @throws SpreadsheetException
     */
    public function testStringFormattedCell()
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('C4'),
            $this->cellService->getFormattedValue($this->sheet->getCell('C4'))
        );

        // assert data from value
        $this->assertEquals('Test123', $cellValue->getCalculatedValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertFalse($cellValue->isRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }

    /**
     * @throws SpreadsheetException
     */
    public function testHyperlinkCell()
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('D4'),
            $this->cellService->getFormattedValue($this->sheet->getCell('D4'))
        );

        // assert data from value
        $this->assertEquals('Link', $cellValue->getCalculatedValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertFalse($cellValue->isRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEquals('http://www.google.de/', $cellValue->getHyperlink());
        $this->assertEquals('', $cellValue->getHyperlinkTitle());
    }

    /**
     * @throws SpreadsheetException
     */
    public function testSuperscriptCell()
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('E5'),
            $this->cellService->getFormattedValue($this->sheet->getCell('E5'))
        );

        // assert data from value
        $this->assertEquals('Hoch', $cellValue->getCalculatedValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertFalse($cellValue->isRichText());
        $this->assertTrue($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }

    /**
     * @throws SpreadsheetException
     */
    public function testSubcriptCell()
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('D5'),
            $this->cellService->getFormattedValue($this->sheet->getCell('D5'))
        );

        // assert data from value
        $this->assertEquals('Hoch Test Tief', $cellValue->getCalculatedValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertTrue($cellValue->isRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }


    /**
     * @throws SpreadsheetException
     */
    public function testAllValueObjectFields()
    {
        $cellValue = CellDataValueObject::create(
            $this->sheet->getCell('B6'),
            $this->cellService->getFormattedValue($this->sheet->getCell('B6')),
            2,
            1,
            [15,20,25]
        );

        $this->assertEquals(2, $cellValue->getRowspan());
        $this->assertEquals(1, $cellValue->getColspan());
        $this->assertGreaterThan(0, $cellValue->getStyleIndex());

        // assert default cell classes by current settings
        $this->assertEquals(
            sprintf(
                'cell cell-type-n cell-style-%d cell-style-15 cell-style-20 cell-style-25',
                $cellValue->getStyleIndex()
            ),
            $cellValue->getClass()
        );
    }
}
