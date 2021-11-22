<?php

namespace Hoogi91\Spreadsheets\Tests\Functional\ViewHelpers;

use TYPO3\CMS\Core\Resource\FileReference;

class FileReferenceViewHelperTestCase extends AbstractViewHelperTestCase
{

    public function testRenderWithoutFile(): void
    {
        self::assertNull($this->getView('<test:reader.fileReference uid=""/>')->render());
    }

    /**
     * @testWith [false, 0]
     *           [true, 123]
     */
    public function testRender(bool $expectFileReference, int $fileReferenceUid): void
    {
        $view = $this->getView('<test:reader.fileReference uid="{uid}"/>', ['uid' => $fileReferenceUid]);
        if ($expectFileReference === true) {
            $this->setUpBackendUserFromFixture(1);
            $this->importDataSet(__DIR__ . '/Fixtures/sys_file_reference.xml'); // import dataset first, then render
            self::assertInstanceOf(FileReference::class, $view->render());
        } else {
            self::assertNull($view->render());
        }
    }
}
