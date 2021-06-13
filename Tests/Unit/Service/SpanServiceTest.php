<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\SpanService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->spreadsheet = (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');
        $this->spanService = new SpanService();
    }

    public function testIgnoringOfColumns(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $ignoredColumns = $this->spanService->getIgnoredColumns($worksheet);
        self::assertEquals([], $ignoredColumns);

        # get same cached result
        self::assertSame($ignoredColumns, $this->spanService->getIgnoredColumns($worksheet));
    }

    public function testIgnoringOfRows(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $ignoredRows = $this->spanService->getIgnoredRows($worksheet);
        self::assertEquals([10], $ignoredRows);

        # get same cached result
        self::assertSame($ignoredRows, $this->spanService->getIgnoredRows($worksheet));
    }

    public function testIgnoringOfCells(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $ignoredCells = $this->spanService->getIgnoredCells($worksheet);

        // base cell D2 (horizontal) and B6 (vertical)
        $expectedCells = ['E2', 'B7'];
        // base cell A9
        $expectedCells = array_merge(
            $expectedCells,
            ['B9', 'C9', 'D9', 'E9', 'F9', 'G9'],
            ['A10', 'B10', 'C10', 'D10', 'E10', 'F10', 'G10']
        );
        self::assertEquals(self::sort($expectedCells), self::sort($ignoredCells));

        # get same cached result
        self::assertSame($ignoredCells, $this->spanService->getIgnoredCells($worksheet));
    }

    public function testMergingOfCells(): void
    {
        $worksheet = $this->spreadsheet->getSheet(0);
        $mergedCells = $this->spanService->getMergedCells($worksheet);

        foreach ($mergedCells as $key => $config) {
            self::assertArrayHasKey('additionalStyleIndexes', $config);
        }

        self::assertEquals(
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
                'A9' => [
                    'additionalStyleIndexes' => $mergedCells['A9']['additionalStyleIndexes'],
                    'colspan' => 7,
                    'rowspan' => 2,
                ],
            ],
            $mergedCells
        );

        # get same cached result
        self::assertSame($mergedCells, $this->spanService->getMergedCells($worksheet));
    }

    private static function sort(array $array): array
    {
        sort($array);
        return $array;
    }
}
