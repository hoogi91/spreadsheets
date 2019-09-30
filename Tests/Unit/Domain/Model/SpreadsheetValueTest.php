<?php

namespace Hoogi91\Spreadsheets\Tests\Domain\Model;

use GeorgRinger\News\Domain\Model\FileReference;
use Hoogi91\Spreadsheets\Service\ExtractorService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\FileRepository;
use Hoogi91\Spreadsheets\Domain\Model\SpreadsheetValue;

/**
 * Class SpreadsheetValueTest
 * @package Hoogi91\Spreadsheets\Tests\Domain\Model
 */
class SpreadsheetValueTest extends UnitTestCase
{

    protected $sheetData = [
        // file reference uid
        5  => [
            // sheet index
            1 => [
                'name' => 'Worksheet Name 1',
            ],
            // sheet index
            2 => [
                'name' => 'Worksheet Name 2',
            ],
        ],
        // file reference uid
        10 => [
            // sheet index
            1 => [
                'name' => 'Worksheet Math',
            ],
            // sheet index
            2 => [
                'name' => 'Worksheet Finance',
            ],
        ],
    ];

    /**
     * @test
     */
    public function testCreationFromDatabaseString()
    {
        $databaseString = 'file:5|1!D2:G5!vertical';
        $value = SpreadsheetValue::createFromDatabaseString($databaseString, $this->sheetData);

        // assert data from value
        $this->assertEquals(5, $value->getFileReferenceUid());
        $this->assertEquals(1, $value->getSheetIndex());
        $this->assertEquals('D2:G5', $value->getSelection());
        $this->assertEquals('Worksheet Name 1', $value->getSheetName());
        $this->assertEquals('Worksheet Name 1!D2:G5!vertical', $value->getFormattedValue());
        $this->assertEquals($databaseString, $value->getDatabaseValue());
    }

    /**
     * @test
     */
    public function testCreationFromDatabaseStringAndCorrectSheetSelection()
    {
        $databaseString = 'file:10|2!A2:B5';
        $value = SpreadsheetValue::createFromDatabaseString($databaseString, $this->sheetData);

        // assert data from value
        $this->assertEquals(10, $value->getFileReferenceUid());
        $this->assertEquals(2, $value->getSheetIndex());
        $this->assertEquals('A2:B5', $value->getSelection());
        $this->assertEquals('Worksheet Finance', $value->getSheetName());
        $this->assertEquals('Worksheet Finance!A2:B5!horizontal', $value->getFormattedValue());
        $this->assertEquals($databaseString . '!horizontal', $value->getDatabaseValue());
    }

    /**
     * @test
     */
    public function testCreationFromDatabaseStringWithoutFilePrefix()
    {
        $databaseString = '5|1!D2:G5!vertical';
        $value = SpreadsheetValue::createFromDatabaseString($databaseString, $this->sheetData);

        // assert data from value
        $this->assertEquals(5, $value->getFileReferenceUid());
        $this->assertEquals(1, $value->getSheetIndex());
        $this->assertEquals('D2:G5', $value->getSelection());
        $this->assertEquals('Worksheet Name 1', $value->getSheetName());
        $this->assertEquals('Worksheet Name 1!D2:G5!vertical', $value->getFormattedValue());
        $this->assertEquals('file:' . $databaseString, $value->getDatabaseValue());
    }

    /**
     * @test
     */
    public function testCreationFromDatabaseStringOnUnknown()
    {
        $databaseString = 'file:99|99!A1:B2';
        $value = SpreadsheetValue::createFromDatabaseString($databaseString, $this->sheetData);

        // assert data from value
        $this->assertEquals(99, $value->getFileReferenceUid());
        $this->assertEquals(99, $value->getSheetIndex());
        $this->assertEquals('A1:B2', $value->getSelection());
        $this->assertEquals('', $value->getSheetName());
        $this->assertEquals('A1:B2!horizontal', $value->getFormattedValue());
        $this->assertEquals($databaseString . '!horizontal', $value->getDatabaseValue());
    }

    /**
     * @test
     */
    public function testSetterAndGetter()
    {
        $value = new SpreadsheetValue($this->createFileRepositoryMock($this->createFileReferenceMock(
            'fixture.xlsx',
            'xlsx'
        )));

        $value->setFileReferenceUid(10);
        $this->assertEquals(10, $value->getFileReferenceUid());

        $value->setSheetIndex(5);
        $this->assertEquals(5, $value->getSheetIndex());

        $value->setSheetName('Lorem ipsum');
        $this->assertEquals('Lorem ipsum', $value->getSheetName());

        $value->setSelection('A3:X25');
        $this->assertEquals('A3:X25', $value->getSelection());

        $value->setDirectionOfSelection(ExtractorService::EXTRACT_DIRECTION_VERTICAL);
        $this->assertEquals(ExtractorService::EXTRACT_DIRECTION_VERTICAL, $value->getDirectionOfSelection());

        // assert default formatted value
        $this->assertEquals('Lorem ipsum!A3:X25!vertical', $value->getFormattedValue());

        // assert formatted value without sheet name
        $value->setSelection('B5:Y10');
        $value->setSheetName('');
        $this->assertEquals('B5:Y10!vertical', $value->getFormattedValue());

        // assert formatted value without selection
        $value->setSelection('');
        $value->setSheetName('Lorem ipsum dolor sit amet');
        $value->setDirectionOfSelection(ExtractorService::EXTRACT_DIRECTION_HORIZONTAL);
        $this->assertEquals('Lorem ipsum dolor sit amet!horizontal', $value->getFormattedValue());

        // assert formatted on magic __toString method
        $value->setSelection('D5:Q10');
        $value->setSheetName('Worksheet 1');
        $this->assertEquals('Worksheet 1!D5:Q10!horizontal', (string)$value);
    }

    /**
     * @test
     */
    public function testGetFileReference()
    {
        $value = new SpreadsheetValue($this->createFileRepositoryMock($this->createFileReferenceMock(
            'fixture.xlsx',
            'xlsx'
        )));

        // assert that item with file reference is returned
        $value->setFileReferenceUid(123);
        $this->assertEquals('xlsx', $value->getFileReference()->getExtension());
        $this->assertEquals(
            dirname(__DIR__, 2) . '/Fixtures/fixture.xlsx',
            $value->getFileReference()->getForLocalProcessing()
        );

        // assert correct exception handling on missing file reference uid
        $value->setFileReferenceUid(0);
        $this->assertNull($value->getFileReference());

        // assert that uid 123 should be returned from static cache
        $value->setFileReferenceUid(123);
        $this->assertEquals('xlsx', $value->getFileReference()->getExtension());
        $this->assertEquals(
            dirname(__DIR__, 2) . '/Fixtures/fixture.xlsx',
            $value->getFileReference()->getForLocalProcessing()
        );
    }

    /**
     * @param mixed $result
     *
     * @return MockObject|FileRepository
     */
    protected function createFileRepositoryMock($result)
    {
        $fileRepositoryMock = $this->getMockBuilder(FileRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findFileReferenceByUid'])
            ->getMock();

        $fileRepositoryMock->method('findFileReferenceByUid')->willReturnCallback(
            function ($fileReferenceUid) use ($result) {
                if ($fileReferenceUid === 0) {
                    return false;
                }
                return $result;
            }
        );

        return $fileRepositoryMock;
    }

    /**
     * get file referece mock of fixture file
     *
     * @param string $file
     * @param string $extension
     *
     * @return MockObject
     */
    protected function createFileReferenceMock($file, $extension)
    {
        $fileReferenceMock = $this->getMockBuilder(FileReference::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getExtension',
                'getForLocalProcessing',
            ])
            ->getMock();
        $fileReferenceMock->method('getExtension')->willReturn($extension);
        $fileReferenceMock->method('getForLocalProcessing')->willReturn(
            dirname(__DIR__, 2) . '/Fixtures/' . $file
        );

        return $fileReferenceMock;
    }

}
