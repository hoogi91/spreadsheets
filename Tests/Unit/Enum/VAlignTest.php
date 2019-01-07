<?php

namespace Hoogi91\Spreadsheets\Tests\Enum;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Hoogi91\Spreadsheets\Enum\VAlign;

/**
 * Class VAlignTest
 * @package Hoogi91\Spreadsheets\Tests\Enum
 */
class VAlignTest extends UnitTestCase
{

    /**
     * test if required mappings are available
     */
    public function testMappingUnknown()
    {
        $this->assertEquals('baseline', VAlign::map('unknown'));
        $this->assertEmpty(VAlign::mapHandsOnTable('unknown'));
    }

    /**
     * @test
     */
    public function testMappingAlignments()
    {
        $this->assertInternalType('string', VAlign::map(Alignment::VERTICAL_BOTTOM));
        $this->assertInternalType('string', VAlign::map(Alignment::VERTICAL_TOP));
        $this->assertInternalType('string', VAlign::map(Alignment::VERTICAL_CENTER));
        $this->assertInternalType('string', VAlign::map(Alignment::VERTICAL_JUSTIFY));
        $this->assertNotEmpty(VAlign::map(Alignment::VERTICAL_BOTTOM));
        $this->assertNotEmpty(VAlign::map(Alignment::VERTICAL_TOP));
        $this->assertNotEmpty(VAlign::map(Alignment::VERTICAL_CENTER));
        $this->assertNotEmpty(VAlign::map(Alignment::VERTICAL_JUSTIFY));
    }

    /**
     * @test
     */
    public function testHandsonTableMappingAlignments()
    {
        $this->assertInternalType('string', VAlign::mapHandsOnTable(Alignment::VERTICAL_BOTTOM));
        $this->assertInternalType('string', VAlign::mapHandsOnTable(Alignment::VERTICAL_TOP));
        $this->assertInternalType('string', VAlign::mapHandsOnTable(Alignment::VERTICAL_CENTER));
        $this->assertInternalType('string', VAlign::mapHandsOnTable(Alignment::VERTICAL_JUSTIFY));
        $this->assertNotEmpty(VAlign::mapHandsOnTable(Alignment::VERTICAL_BOTTOM));
        $this->assertNotEmpty(VAlign::mapHandsOnTable(Alignment::VERTICAL_TOP));
        $this->assertNotEmpty(VAlign::mapHandsOnTable(Alignment::VERTICAL_CENTER));
        $this->assertNotEmpty(VAlign::mapHandsOnTable(Alignment::VERTICAL_JUSTIFY));
    }
}
