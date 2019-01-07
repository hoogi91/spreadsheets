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
