<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\SpanService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class SpanServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class SpanServiceTest extends UnitTestCase
{
    /**
     * @var SpanService
     */
    private $spanService;

    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    /**
     * @throws SpreadsheetException
     */
    protected function setUp()
    {
        parent::setUp();
        $this->spreadsheet = (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');
        $this->spanService = new SpanService();
    }

    public function testIgnoringOfColumns(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $this->assertEqualsArrays([], $this->spanService->getIgnoredColumns($worksheet));
    }

    public function testIgnoringOfRows(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $this->assertEqualsArrays([], $this->spanService->getIgnoredRows($worksheet));
    }

    public function testIgnoringOfCells(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $this->assertEqualsArrays(['E2', 'B7'], $this->spanService->getIgnoredCells($worksheet));
    }

    public function testMergingOfCells(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $mergedCells = $this->spanService->getMergedCells($worksheet);

        foreach ($mergedCells as $key => $config) {
            $this->assertArrayHasKey('additionalStyleIndexes', $config);
        }

        $this->assertEqualsArrays(
            [
                'B6' => [
                    'additionalStyleIndexes' => $mergedCells['B6']['additionalStyleIndexes'],
                    'colspan' => 1,
                    'rowspan' => 2,
                ],
                'D2' => [
                    'additionalStyleIndexes' => $mergedCells['D2']['additionalStyleIndexes'],
                    'colspan' => 2,
                    'rowspan' => 1,
                ],
            ],
            $mergedCells
        );
    }

    /**
     * @param array $expected
     * @param array $actual
     * @param string $message
     */
    protected function assertEqualsArrays($expected, $actual, $message = ''): void
    {
        $this->recursiveSort($expected);
        $this->recursiveSort($actual);
        $this->assertSame($expected, $actual, $message);
    }

    /**
     * @param array $array
     */
    protected function recursiveSort(&$array): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveSort($value);
            }
        }
        unset($value);
        ksort($array);
    }
}
