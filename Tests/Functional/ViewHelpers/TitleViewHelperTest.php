<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Functional\ViewHelpers;

use TYPO3\CMS\Core\Resource\FileRepository;

class TitleViewHelperTest extends AbstractViewHelperTestCase
{
    public function testRenderWithoutFile(): void
    {
        self::assertEmpty($this->getView('<test:reader.sheet.title/>')->render());
    }

    /**
     * @testWith ["Fixture1", 0]
     *           ["Fixture2", 1]
     *           ["", 100]
     */
    public function testRender(string $expected, int $sheetIndex): void
    {
        if (method_exists(\TYPO3\CMS\Core\Database\Connection::class, 'createSchemaManager') === false) {
            $this->markTestSkipped('Testing framework can not handle data import without this method which is missing below TYPO3 v12.');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_file_reference.csv');
        $fileReference = $this->getContainer()->get(FileRepository::class)->findFileReferenceByUid(123);
        self::assertEquals(
            $expected,
            $this->getView(
                '<test:reader.sheet.title file="{file}" index="{index}"/>',
                ['file' => $fileReference, 'index' => $sheetIndex]
            )->render()
        );
    }
}
