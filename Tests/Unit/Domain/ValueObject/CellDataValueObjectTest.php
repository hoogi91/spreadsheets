<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Domain\ValueObject;

use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use Hoogi91\Spreadsheets\Tests\Unit\TsfeSetupTrait;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class CellDataValueObjectTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Domain\ValueObject
 */
class CellDataValueObjectTest extends UnitTestCase
{
    use TsfeSetupTrait;

    /**
     * @var Worksheet
     */
    private $sheet;

    protected function setUp(): void
    {
        parent::setUp();
        self::setupDefaultTSFE();
        $this->sheet = (new Xlsx())->load(dirname(__DIR__, 3) . '/Fixtures/01_fixture.xlsx')->getSheet(0);
    }

    public function testDefaultCell(): void
    {
        $cellValue = new CellDataValueObject($this->sheet->getCell('A1'), 'my-rendered-value');

        // assert data from value
        self::assertEquals('my-rendered-value', $cellValue->getRenderedValue());
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
        $cellValue = new CellDataValueObject($this->sheet->getCell('A4'), 'my-rendered-value');

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
        $cellValue = new CellDataValueObject($this->sheet->getCell('C4'), 'my-rendered-value');

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
        $cellValue = new CellDataValueObject($this->sheet->getCell('D4'), 'my-rendered-value');

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
        $cellValue = new CellDataValueObject($this->sheet->getCell('E5'), 'my-rendered-value');

        // assert data from value
        self::assertEquals('Hoch', $cellValue->getCalculatedValue());
        self::assertEquals(DataType::TYPE_STRING, $cellValue->getDataType());
        self::assertFalse($cellValue->isRichText());
        self::assertFalse($cellValue->isSubscript());
        self::assertEmpty($cellValue->getHyperlink());
        self::assertEmpty($cellValue->getHyperlinkTitle());

        self::markTestIncomplete(
            "Currently superscript has a bug in PhpSpreadsheet and can be enabled again after next release >= 1.22.0\n" .
            "https://github.com/PHPOffice/PhpSpreadsheet/pull/2619"
        );
        self::assertTrue($cellValue->isSuperscript());
    }

    public function testSubcriptCell(): void
    {
        $cellValue = new CellDataValueObject($this->sheet->getCell('D5'), 'my-rendered-value');

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
        $cellValue = CellDataValueObject::create($this->sheet->getCell('B6'), 'my-rendered-value', 2, 1, [15, 20, 25]);

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
            'my-rendered-value',
            2,
            2,
            [15, 20, 25],
            ['backendCellClasses' => ['c', 't']]
        );

        self::assertEquals('{"val":"2015","row":2,"col":2,"css":"c-t"}', json_encode($cellValue));
    }
}
