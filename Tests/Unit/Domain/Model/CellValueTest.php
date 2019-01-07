<?php

namespace Hoogi91\Spreadsheets\Tests\Domain\Model;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Hoogi91\Spreadsheets\Domain\Model\CellValue;

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

    protected function setUp()
    {
        parent::setUp();
        $fixtureFile01 = dirname(__DIR__, 3) . '/Fixtures/01_fixture.xlsx';
        $reader = new Xlsx();
        $sheet = $reader->load($fixtureFile01);
        $this->sheet = $sheet->getSheet(0);
    }

    /**
     * @test
     */
    public function testDefaultCell()
    {
        $cellValue = new CellValue($this->sheet->getCell('A1'));

        // assert data from value
        $this->assertEquals('2014', $cellValue->getValue());
        $this->assertEquals(DataType::TYPE_NUMERIC, $cellValue->getType());
        $this->assertFalse($cellValue->getIsRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }

    /**
     * @test
     */
    public function testSpecialCharsCell()
    {
        $cellValue = new CellValue($this->sheet->getCell('A4'));

        // assert data from value
        $this->assertEquals('©™§∆', $cellValue->getValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertFalse($cellValue->getIsRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }

    /**
     * @test
     */
    public function testStringFormattedCell()
    {
        $cellValue = new CellValue($this->sheet->getCell('C4'));

        // assert data from value
        $this->assertEquals('Test123', $cellValue->getValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertFalse($cellValue->getIsRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }

    /**
     * @test
     */
    public function testHyperlinkCell()
    {
        $cellValue = new CellValue($this->sheet->getCell('D4'));

        // assert data from value
        $this->assertEquals('Link', $cellValue->getValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertFalse($cellValue->getIsRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEquals('http://www.google.de/', $cellValue->getHyperlink());
        $this->assertEquals('', $cellValue->getHyperlinkTitle());
    }

    /**
     * @test
     */
    public function testSuperscriptCell()
    {
        $cellValue = new CellValue($this->sheet->getCell('D5'));

        // assert data from value
        $this->assertEquals('Hoch', $cellValue->getValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertFalse($cellValue->getIsRichText());
        $this->assertTrue($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }

    /**
     * @test
     */
    public function testSubcriptCell()
    {
        $cellValue = new CellValue($this->sheet->getCell('E5'));

        // assert data from value
        $this->assertEquals('Hoch Test Tief', $cellValue->getValue());
        $this->assertEquals(DataType::TYPE_STRING, $cellValue->getType());
        $this->assertTrue($cellValue->getIsRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());
        $this->assertEmpty($cellValue->getHyperlink());
        $this->assertEmpty($cellValue->getHyperlinkTitle());
    }


    /**
     * @test
     */
    public function testSetterAndGetter()
    {
        $cell = $this->sheet->getCell('E5');
        $cellValue = new CellValue($cell);

        $this->assertEquals($cell, $cellValue->getCell());
        $this->assertTrue($cellValue->getIsRichText());
        $this->assertFalse($cellValue->isSuperscript());
        $this->assertFalse($cellValue->isSubscript());

        $cellValue->setValue(1500);
        $this->assertEquals(1500, $cellValue->getValue());

        $cellValue->setType(DataType::TYPE_NUMERIC);
        $this->assertEquals(DataType::TYPE_NUMERIC, $cellValue->getType());

        $this->assertGreaterThan(0, $cellValue->getStyleIndex());

        $cellValue->setAdditionalStyleIndexes([15, 20, 25]);
        $this->assertEquals([15, 20, 25], $cellValue->getAdditionalStyleIndexes());

        $cellValue->setHyperlink('http://www.google.de/');
        $this->assertEquals('http://www.google.de/', $cellValue->getHyperlink());

        $cellValue->setHyperlinkTitle('Google Inc.');
        $this->assertEquals('Google Inc.', $cellValue->getHyperlinkTitle());

        $cellValue->setRowspan(2);
        $this->assertEquals(2, $cellValue->getRowspan());

        $cellValue->setColspan(5);
        $this->assertEquals(5, $cellValue->getColspan());

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
