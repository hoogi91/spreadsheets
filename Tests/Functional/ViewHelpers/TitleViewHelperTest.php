<?php

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
        $this->setUpBackendUserFromFixture(1);
        $this->importDataSet(__DIR__ . '/Fixtures/sys_file_reference.xml');
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
