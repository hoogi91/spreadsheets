<?php

namespace Hoogi91\Spreadsheets\Tests\Enum;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Hoogi91\Spreadsheets\Enum\BorderStyle;

/**
 * Class BorderStyle
 * @package Hoogi91\Spreadsheets\Tests\Enum
 */
class BorderStyleTest extends UnitTestCase
{

    const STRING_PATTERN = '/^\dpx (solid|double|dashed|dotted)$/i';

    /**
     * test if required mappings are available
     */
    public function testMappingUnknown()
    {
        $this->assertStringEndsWith('solid', BorderStyle::map('unknown'));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map('unknown'));
    }

    /**
     * @test
     */
    public function testMappingStyle()
    {
        $this->assertEquals('none', BorderStyle::map(Border::BORDER_NONE));
        $this->assertStringEndsWith('dashed', BorderStyle::map(Border::BORDER_DASHDOT));
        $this->assertStringEndsWith('dotted', BorderStyle::map(Border::BORDER_DASHDOTDOT));
        $this->assertStringEndsWith('dashed', BorderStyle::map(Border::BORDER_DASHED));
        $this->assertStringEndsWith('dotted', BorderStyle::map(Border::BORDER_DOTTED));
        $this->assertStringEndsWith('double', BorderStyle::map(Border::BORDER_DOUBLE));
        $this->assertStringEndsWith('solid', BorderStyle::map(Border::BORDER_HAIR));
        $this->assertStringEndsWith('solid', BorderStyle::map(Border::BORDER_MEDIUM));
        $this->assertStringEndsWith('dashed', BorderStyle::map(Border::BORDER_MEDIUMDASHDOT));
        $this->assertStringEndsWith('dotted', BorderStyle::map(Border::BORDER_MEDIUMDASHDOTDOT));
        $this->assertStringEndsWith('dashed', BorderStyle::map(Border::BORDER_MEDIUMDASHED));
        $this->assertStringEndsWith('dashed', BorderStyle::map(Border::BORDER_SLANTDASHDOT));
        $this->assertStringEndsWith('solid', BorderStyle::map(Border::BORDER_THICK));
        $this->assertStringEndsWith('solid', BorderStyle::map(Border::BORDER_THIN));
    }

    /**
     * @test
     */
    public function testMappingFormat()
    {
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_DASHDOT));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_DASHDOTDOT));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_DASHED));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_DOTTED));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_DOUBLE));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_HAIR));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_MEDIUM));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_MEDIUMDASHDOT));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_MEDIUMDASHDOTDOT));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_MEDIUMDASHED));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_SLANTDASHDOT));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_THICK));
        $this->assertRegExp(self::STRING_PATTERN, BorderStyle::map(Border::BORDER_THIN));
    }
}
