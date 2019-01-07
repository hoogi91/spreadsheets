<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Hoogi91\Spreadsheets\Traits\SheetIndexTrait;

/**
 * Class AbstractSpreadsheetServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
abstract class AbstractSpreadsheetServiceTest extends UnitTestCase
{

    const TEST_SHEET_INDEX = 0;
    const FAIL_SHEET_INDEX = 100;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @var SheetIndexTrait
     */
    protected $currentService;

    /**
     * initialize with default XLSX fixture table and
     * simplified typoscript frontend controller object to habe locale_all configuration set
     *
     * @throws ReaderException
     */
    protected function setUp()
    {
        parent::setUp();

        // create xlsx reader and load default fixture spreadsheet
        $reader = new Xlsx();
        $this->spreadsheet = $reader->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');

        // add simplified globals TSFE object to enable locale handling
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->config['config']['locale_all'] = 'en_US';

        // set current service as vairable
        $this->currentService = $this->setService();
    }

    /**
     * @return SheetIndexTrait
     */
    abstract protected function setService();

    /**
     * @return Spreadsheet
     */
    public function getFixtureSpreadsheet()
    {
        return $this->spreadsheet;
    }

    /**
     * @return SheetIndexTrait
     */
    public function getCurrentService()
    {
        return $this->currentService;
    }

    /**
     * @test
     */
    public function testSettingsSheetIndex()
    {
        $service = $this->getCurrentService();

        $this->assertTrue(method_exists($service, 'setSheetIndex'));
        $this->assertTrue(method_exists($service, 'getSheetIndex'));

        // check if sheet index can be set
        $this->assertEquals(1, $service->setSheetIndex(1)->getSheetIndex());
        $this->assertEquals(0, $service->setSheetIndex(static::TEST_SHEET_INDEX)->getSheetIndex());

        // check if sheet index can throw out of bounds
        $this->expectException(SpreadsheetException::class);
        $this->expectExceptionMessage('You tried to set a sheet active by the out of bounds index');
        $service->setSheetIndex(static::FAIL_SHEET_INDEX);
    }
}
