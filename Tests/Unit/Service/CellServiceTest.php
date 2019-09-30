<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use Hoogi91\Spreadsheets\Service\CellService;

/**
 * Class CellServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class CellServiceTest extends AbstractSpreadsheetServiceTest
{

    const TEST_FORMATTING_SHEET_INDEX = 1;

    /**
     * @return CellService
     */
    protected function setService()
    {
        return new CellService($this->getFixtureSpreadsheet());
    }

    /**
     * @test
     */
    public function testReadingOfCellValues()
    {
        /** @var CellService $cellService */
        $cellService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);
        $worksheet = $this->getFixtureSpreadsheet()->getSheet(static::TEST_SHEET_INDEX);

        // assert data from value
        $this->assertEquals('2014', $cellService->getValue($worksheet->getCell('A1')));
        $this->assertEquals('©™§∆', $cellService->getValue($worksheet->getCell('A4')));
        $this->assertEquals('Test123', $cellService->getValue($worksheet->getCell('C4')));
        $this->assertEquals('Link', $cellService->getValue($worksheet->getCell('D4')));
        $this->assertEquals('Hoch', $cellService->getValue($worksheet->getCell('D5')));
        $this->assertEquals('Hoch', $cellService->getValue($worksheet->getCell('D5')));
        $this->assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $cellService->getValue($worksheet->getCell('E5'))
        );
    }

    /**
     * @test
     */
    public function testReadingOfRawCellValues()
    {
        /** @var CellService $cellService */
        $cellService = $this->getCurrentService()->setSheetIndex(static::TEST_FORMATTING_SHEET_INDEX);
        $worksheet = $this->getFixtureSpreadsheet()->getSheet(static::TEST_FORMATTING_SHEET_INDEX);

        // assert data from value
        $this->assertEquals('=A1+B1', $cellService->getValue($worksheet->getCell('B2'), false, false));
        $this->assertEquals('=A1-B1', $cellService->getValue($worksheet->getCell('B3'), false, false));
        $this->assertEquals('=A1*B1', $cellService->getValue($worksheet->getCell('B4'), false, false));
        $this->assertEquals('=B1/A1', $cellService->getValue($worksheet->getCell('B5'), false, false));
    }

    /**
     * @test
     */
    public function testReadingOfCalculatedCellValues()
    {
        /** @var CellService $cellService */
        $cellService = $this->getCurrentService()->setSheetIndex(static::TEST_FORMATTING_SHEET_INDEX);
        $worksheet = $this->getFixtureSpreadsheet()->getSheet(static::TEST_FORMATTING_SHEET_INDEX);

        // assert data from value
        $this->assertEquals(579.0, $cellService->getValue($worksheet->getCell('B2'), true, false));
        $this->assertEquals(-333, $cellService->getValue($worksheet->getCell('B3'), true, false));
        $this->assertEquals(56088, $cellService->getValue($worksheet->getCell('B4'), true, false));
        $this->assertEquals(3.7073170731707319, $cellService->getValue($worksheet->getCell('B5'), true, false));
    }

    /**
     * @test
     */
    public function testReadingOfCalculatedAndFormattedValues()
    {
        /** @var CellService $cellService */
        $cellService = $this->getCurrentService()->setSheetIndex(static::TEST_FORMATTING_SHEET_INDEX);
        $worksheet = $this->getFixtureSpreadsheet()->getSheet(static::TEST_FORMATTING_SHEET_INDEX);

        // assert data from value
        $this->assertEquals('5.8E+2', $cellService->getValue($worksheet->getCell('C2')));
        $this->assertEquals('5.790E+2', $cellService->getValue($worksheet->getCell('C9')));
        $this->assertEquals('-333.0 €', $cellService->getValue($worksheet->getCell('C3')));
        $this->assertEquals('56,088.000', $cellService->getValue($worksheet->getCell('C4')));
        $this->assertEquals('¥3.707', $cellService->getValue($worksheet->getCell('C5')));
        $this->assertEquals('3.7 ₽', $cellService->getValue($worksheet->getCell('C6')));
        $this->assertEquals('370.73%', $cellService->getValue($worksheet->getCell('C7')));

        // check date field
        $this->assertEquals(43544.0, $cellService->getValue($worksheet->getCell('C8'), true, false));
        $this->assertEquals('Wednesday, March 20, 2019', $cellService->getValue($worksheet->getCell('C8')));
    }

    /**
     * @test
     */
    public function testCatchingOfCalculatedCellValues()
    {
        /** @var CellService $cellService */
        $cellService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);

        // create cell mock to get test exception in cell service
        $mockedCell = $this->getMockBuilder(Cell::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getValue',
                'getCalculatedValue',
            ])
            ->getMock();

        $mockedCell->method('getValue')->willReturn('MockValue');
        $mockedCell->method('getCalculatedValue')->willThrowException(new \PhpOffice\PhpSpreadsheet\Exception());

        $this->assertEquals('MockValue', $cellService->getValue($mockedCell));
    }
}
