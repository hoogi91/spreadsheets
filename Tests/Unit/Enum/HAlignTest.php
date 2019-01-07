<?php

namespace Hoogi91\Spreadsheets\Tests\Enum;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Hoogi91\Spreadsheets\Enum\HAlign;

/**
 * Class HAlignTest
 * @package Hoogi91\Spreadsheets\Tests\Enum
 */
class HAlignTest extends UnitTestCase
{

    /**
     * test if required mappings are available
     */
    public function testMappingUnknown()
    {
        $this->assertEmpty(HAlign::map('unknown'));
        $this->assertEmpty(HAlign::mapHandsOnTable('unknown'));
    }

    /**
     * @test
     */
    public function testMappingAlignments()
    {
        $this->assertInternalType('string', HAlign::map(Alignment::HORIZONTAL_LEFT));
        $this->assertInternalType('string', HAlign::map(Alignment::HORIZONTAL_RIGHT));
        $this->assertInternalType('string', HAlign::map(Alignment::HORIZONTAL_CENTER));
        $this->assertInternalType('string', HAlign::map(Alignment::HORIZONTAL_CENTER_CONTINUOUS));
        $this->assertInternalType('string', HAlign::map(Alignment::HORIZONTAL_JUSTIFY));
        $this->assertNotEmpty(HAlign::map(Alignment::HORIZONTAL_LEFT));
        $this->assertNotEmpty(HAlign::map(Alignment::HORIZONTAL_RIGHT));
        $this->assertNotEmpty(HAlign::map(Alignment::HORIZONTAL_CENTER));
        $this->assertNotEmpty(HAlign::map(Alignment::HORIZONTAL_CENTER_CONTINUOUS));
        $this->assertNotEmpty(HAlign::map(Alignment::HORIZONTAL_JUSTIFY));
    }

    /**
     * @test
     */
    public function testHandsonTableMappingAlignments()
    {
        $this->assertInternalType('string', HAlign::mapHandsOnTable(Alignment::HORIZONTAL_LEFT));
        $this->assertInternalType('string', HAlign::mapHandsOnTable(Alignment::HORIZONTAL_RIGHT));
        $this->assertInternalType('string', HAlign::mapHandsOnTable(Alignment::HORIZONTAL_CENTER));
        $this->assertInternalType('string', HAlign::mapHandsOnTable(Alignment::HORIZONTAL_CENTER_CONTINUOUS));
        $this->assertInternalType('string', HAlign::mapHandsOnTable(Alignment::HORIZONTAL_JUSTIFY));
        $this->assertNotEmpty(HAlign::mapHandsOnTable(Alignment::HORIZONTAL_LEFT));
        $this->assertNotEmpty(HAlign::mapHandsOnTable(Alignment::HORIZONTAL_RIGHT));
        $this->assertNotEmpty(HAlign::mapHandsOnTable(Alignment::HORIZONTAL_CENTER));
        $this->assertNotEmpty(HAlign::mapHandsOnTable(Alignment::HORIZONTAL_CENTER_CONTINUOUS));
        $this->assertNotEmpty(HAlign::mapHandsOnTable(Alignment::HORIZONTAL_JUSTIFY));
    }

    /**
     * @test
     */
    public function testHandsonTableMappingDataTypes()
    {
        $this->assertContains('center', HAlign::mapHandsOnTable('', DataType::TYPE_BOOL), '', true);
        $this->assertContains('center', HAlign::mapHandsOnTable('', DataType::TYPE_ERROR), '', true);
        $this->assertContains('right', HAlign::mapHandsOnTable('', DataType::TYPE_FORMULA), '', true);
        $this->assertContains('right', HAlign::mapHandsOnTable('', DataType::TYPE_NUMERIC), '', true);
        $this->assertEmpty(HAlign::mapHandsOnTable('', DataType::TYPE_NULL));
        $this->assertEmpty(HAlign::mapHandsOnTable('', DataType::TYPE_INLINE));
        $this->assertEmpty(HAlign::mapHandsOnTable('', DataType::TYPE_STRING));
        $this->assertEmpty(HAlign::mapHandsOnTable('', DataType::TYPE_STRING2));
    }
}
