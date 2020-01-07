<?php

namespace Hoogi91\Spreadsheets\Tests\Domain\ValueObject;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use Hoogi91\Spreadsheets\Tests\Unit\FileRepositoryMockTrait;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SpreadsheetValueObjectTest
 * @package Hoogi91\Spreadsheets\Tests\Domain\ValueObject
 */
class SpreadsheetValueObjectTest extends UnitTestCase
{

    use FileRepositoryMockTrait;

    public function setUp()
    {
        parent::setUp();

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getContainerMock();
        $container->expects($this->once())->method('get')->willReturn($this->getFileRepositoryMock());
        GeneralUtility::setContainer($container);
    }

    public function testCreationFromDsnString(): void
    {
        $databaseString = 'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        $this->assertEquals(5, $value->getFileReference()->getUid());
        $this->assertEquals(1, $value->getSheetIndex());
        $this->assertEquals('D2:G5', $value->getSelection());
        $this->assertEquals('vertical', $value->getDirectionOfSelection());
        $this->assertEquals($databaseString, $value->getDsn());
    }

    public function testCreationFromLegacyDsnString(): void
    {
        $databaseString = 'file:5|1!D2:G5!vertical';
        $expectedDSN = 'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        $this->assertEquals(5, $value->getFileReference()->getUid());
        $this->assertEquals(1, $value->getSheetIndex());
        $this->assertEquals('D2:G5', $value->getSelection());
        $this->assertEquals('vertical', $value->getDirectionOfSelection());
        $this->assertEquals($expectedDSN, $value->getDsn());
    }

    public function testCreationFromLegacyDsnStringWithoutDirection(): void
    {
        $databaseString = 'file:10|2!A2:B5';
        $expectedDSN = 'spreadsheet://10?index=2&range=A2%3AB5';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        $this->assertEquals(10, $value->getFileReference()->getUid());
        $this->assertEquals(2, $value->getSheetIndex());
        $this->assertEquals('A2:B5', $value->getSelection());
        $this->assertEquals(null, $value->getDirectionOfSelection());
        $this->assertEquals($expectedDSN, $value->getDsn());
    }

    public function testCreationFromLegacyDsnStringWithoutFilePrefix(): void
    {
        $databaseString = '5|1!D2:G5!vertical';
        $expectedDSN = 'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        $this->assertEquals(5, $value->getFileReference()->getUid());
        $this->assertEquals(1, $value->getSheetIndex());
        $this->assertEquals('D2:G5', $value->getSelection());
        $this->assertEquals($expectedDSN, $value->getDsn());
    }

    public function testCreationFromDatabaseStringOnUnknown(): void
    {
        // an invalid DSN exception should be thrown cause the file could not be found
        $this->expectException(InvalidDataSourceNameException::class);

        try {
            $databaseString = 'spreadsheet://0?index=99&range=A1%3AB2';
            DsnValueObject::createFromDSN($databaseString);
        } catch (InvalidDataSourceNameException $exception) {
            // check if the previous exception indicates that TYPO3 was not able to found the file resource
            $this->assertInstanceOf(ResourceDoesNotExistException::class, $exception->getPrevious());
            throw $exception;
        }
    }
}
