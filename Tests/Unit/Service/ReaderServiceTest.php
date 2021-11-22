<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\ReaderService;
use Hoogi91\Spreadsheets\Tests\Unit\FileRepositoryMockTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class ReaderServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class ReaderServiceTest extends UnitTestCase
{
    use FileRepositoryMockTrait;

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
        (new ReaderService())->getSpreadsheet($this->getFileReferenceMock('01_fixture.xlsx', 'xlsx', true));
    }

    public function testReaderExceptionOnInvalidFileReferenceExtension(): void
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionCode(1514909945);
        (new ReaderService())->getSpreadsheet($this->getFileReferenceMock('some-unknwon.ext', 'ext'));
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
        $spreadsheet = (new ReaderService())->getSpreadsheet($this->getFileReferenceMock($filename, $extension));
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
}
