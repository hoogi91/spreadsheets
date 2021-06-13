<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Domain\ValueObject;

use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use Hoogi91\Spreadsheets\Service\CellService;
use Hoogi91\Spreadsheets\Service\StyleService;
use Hoogi91\Spreadsheets\Service\ValueMappingService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class CellDataValueObjectTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Domain\ValueObject
 */
class CellDataValueObjectTest extends UnitTestCase
{

    /**
     * @var Worksheet
     */
    private $sheet;

    /**
     * @var CellService
     */
    private $cellService;

    protected function setUp(): void
    {
        parent::setUp();

        $sheet = (new Xlsx())->load(dirname(__DIR__, 3) . '/Fixtures/01_fixture.xlsx');
        $this->sheet = $sheet->getSheet(0);

        $this->cellService = new CellService(new StyleService(new ValueMappingService()));
    }

    public function testDefaultCell(): void
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('A1'),
            $this->cellService->getFormattedValue($this->sheet->getCell('A1'))
        );

        // assert data from value
        self::assertEquals('2014', $cellValue->getCalculatedValue());
        self::assertEquals(DataType::TYPE_NUMERIC, $cellValue->getDataType());
        self::assertFalse($cellValue->isRichText());
        self::assertFalse($cellValue->isSuperscript());
        self::assertFalse($cellValue->isSubscript());
        self::assertEmpty($cellValue->getHyperlink());
        self::assertEmpty($cellValue->getHyperlinkTitle());
    }

    public function testSpecialCharsCell(): void
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('A4'),
            $this->cellService->getFormattedValue($this->sheet->getCell('A4'))
        );

        // assert data from value
        self::assertEquals('©™§∆', $cellValue->getCalculatedValue());
        self::assertEquals(DataType::TYPE_STRING, $cellValue->getDataType());
        self::assertFalse($cellValue->isRichText());
        self::assertFalse($cellValue->isSuperscript());
        self::assertFalse($cellValue->isSubscript());
        self::assertEmpty($cellValue->getHyperlink());
        self::assertEmpty($cellValue->getHyperlinkTitle());
    }

    public function testStringFormattedCell(): void
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('C4'),
            $this->cellService->getFormattedValue($this->sheet->getCell('C4'))
        );

        // assert data from value
        self::assertEquals('Test123', $cellValue->getCalculatedValue());
        self::assertEquals(DataType::TYPE_STRING, $cellValue->getDataType());
        self::assertFalse($cellValue->isRichText());
        self::assertFalse($cellValue->isSuperscript());
        self::assertFalse($cellValue->isSubscript());
        self::assertEmpty($cellValue->getHyperlink());
        self::assertEmpty($cellValue->getHyperlinkTitle());
    }

    public function testHyperlinkCell(): void
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('D4'),
            $this->cellService->getFormattedValue($this->sheet->getCell('D4'))
        );

        // assert data from value
        self::assertEquals('Link', $cellValue->getCalculatedValue());
        self::assertEquals(DataType::TYPE_STRING, $cellValue->getDataType());
        self::assertFalse($cellValue->isRichText());
        self::assertFalse($cellValue->isSuperscript());
        self::assertFalse($cellValue->isSubscript());
        self::assertEquals('http://www.google.de/', $cellValue->getHyperlink());
        self::assertEquals('', $cellValue->getHyperlinkTitle());
    }

    public function testSuperscriptCell(): void
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('E5'),
            $this->cellService->getFormattedValue($this->sheet->getCell('E5'))
        );

        // assert data from value
        self::assertEquals('Hoch', $cellValue->getCalculatedValue());
        self::assertEquals(DataType::TYPE_STRING, $cellValue->getDataType());
        self::assertFalse($cellValue->isRichText());
        self::assertTrue($cellValue->isSuperscript());
        self::assertFalse($cellValue->isSubscript());
        self::assertEmpty($cellValue->getHyperlink());
        self::assertEmpty($cellValue->getHyperlinkTitle());
    }

    public function testSubcriptCell(): void
    {
        $cellValue = new CellDataValueObject(
            $this->sheet->getCell('D5'),
            $this->cellService->getFormattedValue($this->sheet->getCell('D5'))
        );

        // assert data from value
        self::assertEquals('Hoch Test Tief', $cellValue->getCalculatedValue());
        self::assertEquals(DataType::TYPE_STRING, $cellValue->getDataType());
        self::assertTrue($cellValue->isRichText());
        self::assertFalse($cellValue->isSuperscript());
        self::assertFalse($cellValue->isSubscript());
        self::assertEmpty($cellValue->getHyperlink());
        self::assertEmpty($cellValue->getHyperlinkTitle());
    }

    public function testAllValueObjectFields(): void
    {
        $cellValue = CellDataValueObject::create(
            $this->sheet->getCell('B6'),
            $this->cellService->getFormattedValue($this->sheet->getCell('B6')),
            2,
            1,
            [15, 20, 25]
        );

        self::assertEquals(2, $cellValue->getRowspan());
        self::assertEquals(1, $cellValue->getColspan());
        self::assertGreaterThan(0, $cellValue->getStyleIndex());

        // assert default cell classes by current settings
        self::assertEquals(
            sprintf(
                'cell cell-type-n cell-style-%d cell-style-15 cell-style-20 cell-style-25',
                $cellValue->getStyleIndex()
            ),
            $cellValue->getClass()
        );
    }

    public function testJsonSerializeCellData(): void
    {
        $cellValue = CellDataValueObject::create(
            $this->sheet->getCell('B6'),
            $this->cellService->getFormattedValue($this->sheet->getCell('B6')),
            2,
            2,
            [15, 20, 25],
            ['backendCellClasses' => ['c', 't']]
        );

        self::assertEquals('{"val":"2015","row":2,"col":2,"css":"c-t"}', json_encode($cellValue));
    }
}
