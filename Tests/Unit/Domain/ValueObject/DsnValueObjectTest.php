<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\Domain\ValueObject;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use Hoogi91\Spreadsheets\Tests\Unit\FileRepositoryMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DsnValueObjectTest extends UnitTestCase
{
    use FileRepositoryMockTrait;

    public function setUp(): void
    {
        parent::setUp();

        /** @var ContainerInterface&MockObject $container */
        $container = $this->getContainerMock();
        $container->method('get')->willReturn($this->getFileRepositoryMock());
        GeneralUtility::setContainer($container);
    }

    /**
     * @dataProvider legacyProvider
     */
    public function testLegacyDsnHandling(string $dsn, string $exceptionClassOrFinalDsn): void
    {
        if (is_subclass_of($exceptionClassOrFinalDsn, Throwable::class) === true) {
            $this->expectException($exceptionClassOrFinalDsn);
        }

        $value = DsnValueObject::createFromDSN($dsn);
        self::assertEquals($exceptionClassOrFinalDsn, (string)$value);
        self::assertEquals(5, $value->getFileReference());

        self::assertEquals(
            str_contains($dsn, 'vertical') ? 'vertical' : null,
            $value->getDirectionOfSelection()
        );

        if (str_contains($dsn, '|1')) {
            self::assertEquals(1, $value->getSheetIndex());
            self::assertEquals('D2:G5', $value->getSelection());
        } else {
            self::assertEquals(0, $value->getSheetIndex());
            self::assertNull($value->getSelection());
        }
    }

    /**
     * @return array<string, array<string>>
     */
    public function legacyProvider(): array
    {
        return [
            'unknown file' => ['', InvalidDataSourceNameException::class],
            'with invalid file identifier' => [
                'file:0unknwon|1!D2:G5!vertical',
                InvalidDataSourceNameException::class,
            ],
            'with invalid file reference' => [
                'file:0|1!D2:G5!vertical',
                InvalidDataSourceNameException::class,
            ],
            'with invalid sheet index' => [
                'file:5|-1!D2:G5!vertical',
                InvalidDataSourceNameException::class,
            ],
            'without file prefix' => [
                '5|1!D2:G5!vertical',
                'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical',
            ],
            'without direction' => [
                'file:5|1!D2:G5',
                'spreadsheet://5?index=1&range=D2%3AG5',
            ],
            'only file reference #1' => [
                'file:5|',
                'spreadsheet://5?index=0',
            ],
            'only file reference #2' => [
                'file:5|0',
                'spreadsheet://5?index=0',
            ],
            'valid dsn' => [
                'file:5|1!D2:G5!vertical',
                'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical',
            ],
        ];
    }

    /**
     * @dataProvider dsnProvider
     */
    public function testDsnHandling(string $dsn, string $exceptionClassOrFinalDsn): void
    {
        if (is_subclass_of($exceptionClassOrFinalDsn, Throwable::class) === true) {
            $this->expectException($exceptionClassOrFinalDsn);
        }

        $value = DsnValueObject::createFromDSN($dsn);
        self::assertEquals($exceptionClassOrFinalDsn, (string)$value);
        self::assertEquals(5, $value->getFileReference());
        self::assertEquals(
            str_contains($dsn, 'vertical') ? 'vertical' : null,
            $value->getDirectionOfSelection()
        );

        if (str_contains($dsn, '?index')) {
            self::assertEquals(1, $value->getSheetIndex());
            self::assertEquals('D2:G5', $value->getSelection());
        } else {
            self::assertEquals(0, $value->getSheetIndex());
            self::assertNull($value->getSelection());
        }
    }

    /**
     * @return array<string, array<string>>
     */
    public function dsnProvider(): array
    {
        return [
            'unknown file' => ['', InvalidDataSourceNameException::class],
            'without file prefix' => [
                '5?index=1&range=D2%3AG5&direction=vertical',
                InvalidDataSourceNameException::class,
            ],
            'with invalid file identifier' => [
                'spreadsheet://0unknown?index=1&range=D2%3AG5&direction=vertical',
                InvalidDataSourceNameException::class,
            ],
            'with invalid file reference' => [
                'spreadsheet://0?index=1&range=D2%3AG5&direction=vertical',
                InvalidDataSourceNameException::class,
            ],
            'with invalid sheet index' => [
                'spreadsheet://5?index=-1&range=D2%3AG5&direction=vertical',
                InvalidDataSourceNameException::class,
            ],
            'without direction' => [
                'spreadsheet://5?index=1&range=D2%3AG5',
                'spreadsheet://5?index=1&range=D2%3AG5',
            ],
            'only file reference' => [
                'spreadsheet://5',
                'spreadsheet://5?index=0',
            ],
            'valid dsn' => [
                'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical',
                'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical',
            ],
        ];
    }

    public function testCreationFromDsnString(): void
    {
        $databaseString = 'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        self::assertEquals(5, $value->getFileReference());
        self::assertEquals(1, $value->getSheetIndex());
        self::assertEquals('D2:G5', $value->getSelection());
        self::assertEquals('vertical', $value->getDirectionOfSelection());
        self::assertEquals($databaseString, $value->getDsn());
    }

    public function testCreationFromLegacyDsnString(): void
    {
        $databaseString = 'file:5|1!D2:G5!vertical';
        $expectedDSN = 'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        self::assertEquals(5, $value->getFileReference());
        self::assertEquals(1, $value->getSheetIndex());
        self::assertEquals('D2:G5', $value->getSelection());
        self::assertEquals('vertical', $value->getDirectionOfSelection());
        self::assertEquals($expectedDSN, $value->getDsn());
    }

    public function testCreationFromLegacyDsnStringWithoutDirection(): void
    {
        $databaseString = 'file:10|2!A2:B5';
        $expectedDSN = 'spreadsheet://10?index=2&range=A2%3AB5';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        self::assertEquals(10, $value->getFileReference());
        self::assertEquals(2, $value->getSheetIndex());
        self::assertEquals('A2:B5', $value->getSelection());
        self::assertEquals(null, $value->getDirectionOfSelection());
        self::assertEquals($expectedDSN, $value->getDsn());
    }

    public function testCreationFromLegacyDsnStringWithoutFilePrefix(): void
    {
        $databaseString = '5|1!D2:G5!vertical';
        $expectedDSN = 'spreadsheet://5?index=1&range=D2%3AG5&direction=vertical';
        $value = DsnValueObject::createFromDSN($databaseString);

        // assert data from value
        self::assertEquals(5, $value->getFileReference());
        self::assertEquals(1, $value->getSheetIndex());
        self::assertEquals('D2:G5', $value->getSelection());
        self::assertEquals($expectedDSN, (string)$value);
    }
}
