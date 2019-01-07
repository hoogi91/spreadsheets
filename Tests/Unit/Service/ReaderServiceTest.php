<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Html as HtmlReader;
use PhpOffice\PhpSpreadsheet\Reader\Ods as OdsReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xml as XmlReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use Hoogi91\Spreadsheets\Service\ReaderService;

/**
 * Class ReaderServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class ReaderServiceTest extends UnitTestCase
{

    /**
     * @test
     */
    public function testReaderExceptionOnMissingOriginalFile()
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionCode(1539959214);
        new ReaderService($this->createFileReferenceMock('01_fixture.xlsx', 'xlsx', true));
    }

    /**
     * @test
     */
    public function testReaderExceptionOnInvalidFileReferenceExtension()
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionCode(1514909945);
        new ReaderService($this->createFileReferenceMock('some-unknwon.ext', 'ext'));
    }

    /**
     * @test
     */
    public function testXlxsInstance()
    {
        $readerService = new ReaderService($this->createFileReferenceMock('01_fixture.xlsx', 'xlsx'));

        // start assertions
        $this->assertInstanceOf(XlsxReader::class, $readerService->getReader());
        $this->executeAssertionsOnReaderInstance($readerService);
    }

    /**
     * @test
     */
    public function testXlsInstance()
    {
        $readerService = new ReaderService($this->createFileReferenceMock('02_fixture.xls', 'xls'));

        // start assertions
        $this->assertInstanceOf(XlsReader::class, $readerService->getReader());
        $this->executeAssertionsOnReaderInstance($readerService);
    }

    /**
     * @test
     */
    public function testOdsInstance()
    {
        $readerService = new ReaderService($this->createFileReferenceMock('03_fixture.ods', 'ods'));

        // start assertions
        $this->assertInstanceOf(OdsReader::class, $readerService->getReader());
        $this->executeAssertionsOnReaderInstance($readerService);
    }

    /**
     * @test
     * @
     */
    public function testXmlInstance()
    {
        $this->markTestSkipped('Update XML fixture to test properly');
        $readerService = new ReaderService($this->createFileReferenceMock('04_fixture.xml', 'xml'));

        // start assertions
        $this->assertInstanceOf(XmlReader::class, $readerService->getReader());
        $this->executeAssertionsOnReaderInstance($readerService);
    }

    /**
     * @test
     */
    public function testCsvInstance()
    {
        $readerService = new ReaderService($this->createFileReferenceMock('05_fixture.csv', 'csv'));

        // start assertions
        $this->assertInstanceOf(CsvReader::class, $readerService->getReader());
        $this->executeAssertionsOnReaderInstance($readerService);
    }

    /**
     * @test
     */
    public function testHtmlInstance()
    {
        $this->markTestSkipped('Update HTML fixture to test properly');
        $readerService = new ReaderService($this->createFileReferenceMock('06_fixture.html', 'html'));

        // start assertions
        $this->assertInstanceOf(HtmlReader::class, $readerService->getReader());
        $this->executeAssertionsOnReaderInstance($readerService);
    }

    /**
     * @param ReaderService $readerService
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function executeAssertionsOnReaderInstance($readerService)
    {
        // assert if reader service is successfully initialized
        $this->assertInstanceOf(Spreadsheet::class, $readerService->getSpreadsheet());
        $this->assertInstanceOf(Worksheet::class, $readerService->getSheet(0));

        foreach ($readerService->getSheets() as $index => $sheet) {
            $this->assertInstanceOf(Worksheet::class, $sheet, sprintf(
                'Worksheet at position "%s" is not of type "%s"',
                $index,
                Worksheet::class
            ));
        }
    }

    /**
     * get file referece mock of fixture file
     *
     * @param string $file
     * @param string $extension
     * @param bool   $missingOriginalFile
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createFileReferenceMock($file, $extension, $missingOriginalFile = false)
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
        $fileReferenceMock->method('getOriginalFile')->willReturn($this->createOriginalFileMock(!$missingOriginalFile));
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
