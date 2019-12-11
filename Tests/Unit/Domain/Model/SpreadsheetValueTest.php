<?php

namespace Hoogi91\Spreadsheets\Tests\Domain\Model;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SpreadsheetValueTest
 * @package Hoogi91\Spreadsheets\Tests\Domain\Model
 */
class SpreadsheetValueTest extends UnitTestCase
{

    protected $sheetData = [
        // file reference uid
        5 => [
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

    public function setUp()
    {
        parent::setUp();
        $_this = $this;

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $filRepositoryMock = $this->getMockBuilder(FileRepository::class)->disableOriginalConstructor()->getMock();
        $filRepositoryMock->expects($this->once())->method('findFileReferenceByUid')->willReturnCallback(
            static function (int $fileUid) use ($_this) {
                $mock = $_this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock();
                $mock->method('getUid')->willReturn($fileUid);
                return $mock;
            }
        );

        // add expectation on container and apply to general utility
        $container->expects($this->any())->method('has')->willReturn(true);
        $container->expects($this->once())->method('get')->willReturn($filRepositoryMock);
        GeneralUtility::setContainer($container);
    }

    public function testCreationFromDatabaseString(): void
    {
        $databaseString = 'file:5|1!D2:G5!vertical';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        $this->assertEquals(5, $value->getFileReference()->getUid());
        $this->assertEquals(1, $value->getSheetIndex());
        $this->assertEquals('D2:G5', $value->getSelection());
        $this->assertEquals('vertical', $value->getDirectionOfSelection());
        $this->assertEquals($databaseString, $value->getDsn());
    }

    public function testCreationFromDatabaseStringAndCorrectSheetSelection(): void
    {
        $databaseString = 'file:10|2!A2:B5';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        $this->assertEquals(10, $value->getFileReference()->getUid());
        $this->assertEquals(2, $value->getSheetIndex());
        $this->assertEquals('A2:B5', $value->getSelection());
        $this->assertEquals(null, $value->getDirectionOfSelection());
        $this->assertEquals($databaseString, $value->getDsn());
    }

    public function testCreationFromDatabaseStringWithoutFilePrefix(): void
    {
        $databaseString = '5|1!D2:G5!vertical';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        $this->assertEquals(5, $value->getFileReference()->getUid());
        $this->assertEquals(1, $value->getSheetIndex());
        $this->assertEquals('D2:G5', $value->getSelection());
        $this->assertEquals('file:' . $databaseString, $value->getDsn());
    }

    public function testCreationFromDatabaseStringOnUnknown(): void
    {
        $databaseString = 'file:99|99!A1:B2';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        $this->assertEquals(99, $value->getFileReference()->getUid());
        $this->assertEquals(99, $value->getSheetIndex());
        $this->assertEquals('A1:B2', $value->getSelection());
        $this->assertEquals($databaseString, $value->getDsn());
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
}
