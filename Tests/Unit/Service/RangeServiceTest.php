<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\RangeService;

/**
 * Class RangeServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class RangeServiceTest extends AbstractSpreadsheetServiceTest
{

    /**
     * @return RangeService
     */
    protected function setService()
    {
        return new RangeService($this->getFixtureSpreadsheet());
    }

    /**
     * @test
     */
    public function testRangeConvertingIsEmptyOnUnknownSheet()
    {
        /** @var RangeService $rangeService */
        $rangeService = $this->getCurrentService();

        $this->assertEmpty($rangeService->convert(static::FAIL_SHEET_INDEX, 'A:D'));
    }

    /**
     * @test
     */
    public function testRangeConvertingOfRowSelections()
    {
        /** @var RangeService $rangeService */
        $rangeService = $this->getCurrentService();

        $this->assertEquals('A2:G24', $rangeService->convert(static::TEST_SHEET_INDEX, '2:24'));
        $this->assertEquals('A2:G2', $rangeService->convert(static::TEST_SHEET_INDEX, '2'));
    }

    /**
     * @test
     */
    public function testRangeConvertingOfColumnSelections()
    {
        /** @var RangeService $rangeService */
        $rangeService = $this->getCurrentService();

        $this->assertEquals('B1:D7', $rangeService->convert(static::TEST_SHEET_INDEX, 'B:D'));
        $this->assertEquals('B1:B7', $rangeService->convert(static::TEST_SHEET_INDEX, 'B'));
    }

    /**
     * @test
     */
    public function testRangeConvertingOfColumnAndRowMixSelections()
    {
        /** @var RangeService $rangeService */
        $rangeService = $this->getCurrentService();

        $this->assertEquals('B2', $rangeService->convert(static::TEST_SHEET_INDEX, '2:B'));
        $this->assertEquals('B24', $rangeService->convert(static::TEST_SHEET_INDEX, 'B:24'));
    }

    /**
     * @test
     */
    public function testRangeConvertingOfUncompletedColumnSelection()
    {
        /** @var RangeService $rangeService */
        $rangeService = $this->getCurrentService();

        $this->assertEquals('B2:B24', $rangeService->convert(static::TEST_SHEET_INDEX, 'B2:24'));
        $this->assertEquals('D2:D24', $rangeService->convert(static::TEST_SHEET_INDEX, '2:D24'));
    }

    /**
     * @test
     */
    public function testRangeConvertingOfUncompletedRowSelection()
    {
        /** @var RangeService $rangeService */
        $rangeService = $this->getCurrentService();

        $this->assertEquals('B2:D2', $rangeService->convert(static::TEST_SHEET_INDEX, 'B2:D'));
        $this->assertEquals('B24:D24', $rangeService->convert(static::TEST_SHEET_INDEX, 'B:D24'));
    }
}
