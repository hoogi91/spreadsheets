<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\RangeService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class RangeServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class RangeServiceTest extends UnitTestCase
{
    /**
     * @var RangeService
     */
    private $rangeService;

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
        $this->rangeService = new RangeService();
    }

    public function rangeConvertingDataProvider(): array
    {
        // input ranges will be shrinked to fit into fixture data structure
        return [
            ['2:24', 'A2:G7'],
            ['2', 'A2:G2'],
            ['B:D', 'B1:D7'],
            ['B', 'B1:B7'],
            ['2:B', 'B2:B2'],
            ['B:24', 'B7:B7'],
            ['B2:24', 'B2:B7'],
            ['2:D24', 'D2:D7'],
            ['B2:D', 'B2:D2'],
            ['B:D24', 'B7:D7'],
        ];
    }

    public function testRangeConvertingIsEmptyOnUnknownSheet(): void
    {
        $this->assertEquals('', $this->rangeService->convert(new Worksheet(), 'A:D'));
    }

    /**
     * @param string $input
     * @param string $expectedOutput
     *
     * @dataProvider rangeConvertingDataProvider
     *
     * @throws SpreadsheetException
     */
    public function testRangeConverting(string $input, string $expectedOutput): void
    {
        $this->assertEquals($expectedOutput, $this->rangeService->convert($this->spreadsheet->getSheet(0), $input));
    }
}
