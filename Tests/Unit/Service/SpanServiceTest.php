<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\SpanService;

/**
 * Class SpanServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class SpanServiceTest extends AbstractSpreadsheetServiceTest
{

    /**
     * @return SpanService
     */
    protected function setService()
    {
        return new SpanService($this->getFixtureSpreadsheet());
    }

    /**
     * @test
     */
    public function testIgnoringOfColumns()
    {
        /** @var SpanService $spanService */
        $spanService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);
        $this->assertEqualsArrays([], $spanService->getIgnoredColumns());
    }

    /**
     * @test
     */
    public function testIgnoringOfRows()
    {
        /** @var SpanService $spanService */
        $spanService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);
        $this->assertEqualsArrays([], $spanService->getIgnoredRows());
    }

    /**
     * @test
     */
    public function testIgnoringOfCells()
    {
        /** @var SpanService $spanService */
        $spanService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);
        $this->assertEqualsArrays(['B7', 'E2'], $spanService->getIgnoredCells(), true);
    }

    /**
     * @test
     */
    public function testMergingOfCells()
    {
        /** @var SpanService $spanService */
        $spanService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);
        $mergedCells = $spanService->getMergedCells();

        $this->assertArraySubset([
            'B6' => [
                'colspan' => 1,
                'rowspan' => 2,
            ],
        ], $mergedCells);

        $this->assertArraySubset([
            'D2' => [
                'colspan' => 2,
                'rowspan' => 1,
            ],
        ], $mergedCells);

        foreach ($mergedCells as $key => $config) {
            $this->assertArrayHasKey('additionalStyleIndexes', $config);
        }
    }

    /**
     * @param array  $expected
     * @param array  $actual
     * @param bool   $onlyValues
     * @param string $message
     */
    protected function assertEqualsArrays($expected, $actual, $onlyValues = false, $message = '')
    {
        $this->recursiveSort($expected, $onlyValues);
        $this->recursiveSort($actual, $onlyValues);
        $this->assertSame($expected, $actual, $message);
    }

    /**
     * @param array $array
     * @param bool  $onlyValues
     */
    protected function recursiveSort(&$array, $onlyValues = false)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveSort($value);
            }
        }

        if ($onlyValues === true) {
            sort($array);
        } else {
            ksort($array);
        }
    }
}
