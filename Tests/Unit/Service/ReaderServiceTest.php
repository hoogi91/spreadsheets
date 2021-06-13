<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\ReaderService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * Class ReaderServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class ReaderServiceTest extends UnitTestCase
{
    public function readerTypeDataProvider(): array
    {
        return [
            ['01_fixture.xlsx', 'xlsx'],
            ['02_fixture.xls', 'xls'],
            ['03_fixture.ods', 'ods'],
            ['04_fixture.xml', 'xml'],
            ['05_fixture.csv', 'csv'],
            ['06_fixture.html', 'html'],
        ];
    }

    public function testReaderExceptionOnMissingOriginalFile(): void
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionCode(1539959214);
        (new ReaderService())->getSpreadsheet($this->createFileReferenceMock('01_fixture.xlsx', 'xlsx', true));
    }

    public function testReaderExceptionOnInvalidFileReferenceExtension(): void
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionCode(1514909945);
        (new ReaderService())->getSpreadsheet($this->createFileReferenceMock('some-unknwon.ext', 'ext'));
    }

    /**
     * @param string $filename
     * @param string $extension
     *
     * @dataProvider readerTypeDataProvider
     */
    public function testReaderInstance(string $filename, string $extension): void
    {
        // assert if reader service is successfully initialized and returns spreadsheet
        $spreadsheet = (new ReaderService())->getSpreadsheet($this->createFileReferenceMock($filename, $extension));
        self::assertInstanceOf(Worksheet::class, $spreadsheet->getSheet(0));

        foreach ($spreadsheet->getAllSheets() as $index => $sheet) {
            self::assertInstanceOf(
                Worksheet::class,
                $sheet,
                sprintf(
                    'Worksheet at position "%s" is not of type "%s"',
                    $index,
                    Worksheet::class
                )
            );
        }
    }

    /**
     * get file referece mock of fixture file
     *
     * @param string $file
     * @param string $extension
     * @param bool $missingOriginalFile
     *
     * @return FileReference|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createFileReferenceMock(string $file, string $extension, bool $missingOriginalFile = false)
    {
        $fileReferenceMock = $this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock();

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
     * @return File|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createOriginalFileMock(bool $exists = true)
    {
        $fileMock = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();
        $fileMock->method('exists')->willReturn($exists);
        return $fileMock;
    }
}
