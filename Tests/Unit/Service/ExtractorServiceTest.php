<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use Hoogi91\Spreadsheets\Domain\Model\CellValue;
use Hoogi91\Spreadsheets\Domain\Model\SpreadsheetValue;
use Hoogi91\Spreadsheets\Service\ExtractorService;

/**
 * Class ExtractorServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class ExtractorServiceTest extends AbstractSpreadsheetServiceTest
{

    /**
     * @return ExtractorService
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function setService()
    {
        return new ExtractorService($this->getFixtureSpreadsheet(), static::TEST_SHEET_INDEX);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode  1515668054
     */
    public function testConstructWithNegativeSheetIndex()
    {
        new ExtractorService($this->getFixtureSpreadsheet(), -1);
    }

    /**
     * @test
     */
    public function testCreateFromDatabaseString()
    {
        $this->assertNull(ExtractorService::createFromDatabaseString(''));

        // TODO: the evaluated spreadsheet value needs be mocked to get valid file reference!
        $this->assertNull(ExtractorService::createFromDatabaseString('file:0|0'));
    }

    /**
     * @test
     * @expectedException \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @expectedExceptionCode  1514909945
     */
    public function testCreateFromSpreadsheetValue()
    {
        $invalidFileReferenceValueMock = $this->createSpreadsheetValueMock(null);
        $this->assertNull(ExtractorService::createFromSpreadsheetValue($invalidFileReferenceValueMock));

        $validFileReferenceMock = $this->createSpreadsheetValueMock($this->createFileReferenceMock(
            '01_fixture.xlsx',
            'xlsx'
        ));
        $this->assertInstanceOf(
            ExtractorService::class,
            ExtractorService::createFromSpreadsheetValue($validFileReferenceMock)
        );

        $invalidFileReferenceExtensionMock = $this->createSpreadsheetValueMock($this->createFileReferenceMock(
            '01_fixture.xlsx',
            'ext'
        ));
        ExtractorService::createFromSpreadsheetValue($invalidFileReferenceExtensionMock);
    }

    /**
     * @test
     */
    public function testRangeExtractor()
    {
        /** @var ExtractorService $extractorService */
        $extractorService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);
        $range = $extractorService->rangeToCellArray('A1:E7');

        /** @var CellValue $cellValueA1 */
        $cellValueA1 = $range[1]['A'];
        /** @var CellValue $cellValueE5 */
        $cellValueE5 = $range[5]['E'];

        $this->assertEquals('2014', $cellValueA1->getValue());
        $this->assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $cellValueE5->getValue()
        );
    }

    /**
     * @test
     */
    public function testRangeExtractorWithCellReference()
    {
        /** @var ExtractorService $extractorService */
        $extractorService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);
        $range = $extractorService->rangeToCellArray('A1:E7', true);

        /** @var CellValue $cellValueA1 */
        $cellValueA1 = $range[1][1];
        /** @var CellValue $cellValueE5 */
        $cellValueE5 = $range[5][5];

        $this->assertEquals('2014', $cellValueA1->getValue());
        $this->assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $cellValueE5->getValue()
        );
    }

    /**
     * @test
     */
    public function testHeadDataExtraction()
    {
        /** @var ExtractorService $extractorService */
        $extractorService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);
        $this->assertEmpty($extractorService->getHeadData());
    }

    /**
     * @test
     */
    public function testBodyDataExtraction()
    {
        /** @var ExtractorService $extractorService */
        $extractorService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);

        $bodyData = $extractorService->getBodyData();

        /** @var CellValue $cellValueA1 */
        $cellValueA1 = $bodyData[1][1];
        /** @var CellValue $cellValueE5 */
        $cellValueE5 = $bodyData[5][5];

        $this->assertInternalType('array', $bodyData);
        $this->assertCount(7, $bodyData);
        $this->assertEquals('2014', $cellValueA1->getValue());
        $this->assertEquals(
            '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
            $cellValueE5->getValue()
        );
    }

    /**
     * @test
     */
    public function testStyleExtraction()
    {
        /** @var ExtractorService $extractorService */
        $extractorService = $this->getCurrentService()->setSheetIndex(static::TEST_SHEET_INDEX);

        $styles = $extractorService->getStyles('identifier');
        $this->assertInternalType('string', $styles);
        $this->assertStringStartsWith('#identifier', $styles);
        $this->assertContains('.cell-type-e', $styles);
        $this->assertContains('.cell-type-f', $styles);
        $this->assertContains('.cell-type-inlineStr', $styles);
        $this->assertContains('.cell-type-n', $styles);
        $this->assertContains('.cell-type-s', $styles);
        $this->assertContains('.cell-style-', $styles);
    }

    /**
     * get spreadsheet value mock object
     *
     * @param object|null $fileReference
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createSpreadsheetValueMock($fileReference)
    {
        $mock = $this->getMockBuilder(SpreadsheetValue::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFileReference', 'getSheetIndex'])
            ->getMock();
        $mock->method('getFileReference')->willReturn($fileReference);
        $mock->method('getSheetIndex')->willReturn(static::TEST_SHEET_INDEX);

        return $mock;
    }

    /**
     * get file referece mock of fixture file
     *
     * @param string $file
     * @param string $extension
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createFileReferenceMock($file, $extension)
    {
        $fileReferenceMock = $this->getMockBuilder(FileReference::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getExtension',
                'getOriginalFile',
                'getForLocalProcessing',
            ])
            ->getMock();

        $fileReferenceMock->method('getExtension')->willReturn($extension);
        $fileReferenceMock->method('getOriginalFile')->willReturn($this->createOriginalFileMock());
        $fileReferenceMock->method('getForLocalProcessing')->willReturn(
            dirname(__DIR__, 2) . '/Fixtures/' . $file
        );

        return $fileReferenceMock;
    }

    /**
     * @param bool $exists
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOriginalFileMock($exists = true)
    {
        $fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();
        $fileMock->method('exists')->willReturn($exists);
        return $fileMock;
    }
}
