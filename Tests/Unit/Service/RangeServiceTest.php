<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\RangeService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RangeServiceTest extends UnitTestCase
{
    private RangeService $rangeService;

    private Spreadsheet $spreadsheet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->spreadsheet = (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');
        $this->rangeService = new RangeService();
    }

    /**
     * @return array<int, array<string>>
     */
    public function rangeConvertingDataProvider(): array
    {
        // input ranges will be shrinked to fit into fixture data structure
        return [
            ['2:24', 'A2:G11'],
            ['2', 'A2:G2'],
            ['B:D', 'B1:D11'],
            ['B', 'B1:B11'],
            ['2:B', 'B2:B2'],
            ['B:24', 'B11:B11'],
            ['B2:24', 'B2:B11'],
            ['2:D24', 'D2:D11'],
            ['B2:D', 'B2:D2'],
            ['B:D24', 'B11:D11'],
        ];
    }

    public function testRangeConvertingIsEmptyOnUnknownSheet(): void
    {
        self::assertEquals('', $this->rangeService->convert(new Worksheet(), 'A:D'));
    }

    /**
     * @dataProvider rangeConvertingDataProvider
     */
    public function testRangeConverting(string $input, string $expectedOutput): void
    {
        self::assertEquals($expectedOutput, $this->rangeService->convert($this->spreadsheet->getSheet(0), $input));
    }
}
