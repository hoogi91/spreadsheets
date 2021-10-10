<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\ViewHelpers;

use Hoogi91\Spreadsheets\Tests\Unit\FileRepositoryMockTrait;
use Hoogi91\Spreadsheets\ViewHelpers\Reader\FileReferenceViewHelper;
use Nimut\TestingFramework\TestCase\ViewHelperBaseTestcase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\FileReference;

class FileReferenceViewHelperTest extends ViewHelperBaseTestcase
{
    use FileRepositoryMockTrait;

    /**
     * @var MockObject|FileReferenceViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $fileRepository = $this->getFileRepositoryMock();
        $this->viewHelper = $this->getMockBuilder(FileReferenceViewHelper::class)
            ->setConstructorArgs([$fileRepository])
            ->setMethods(['renderChildren'])
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    public function testRenderWithoutFile(): void
    {
        $this->viewHelper->initializeArguments();
        self::assertNull($this->viewHelper->render());
    }

    /**
     * @testWith [false, 0]
     *           [true, 123]
     */
    public function testRender(bool $expectFileReference, int $fileReferenceUid): void
    {
        $this->setArgumentsUnderTest($this->viewHelper, ['uid' => $fileReferenceUid]);
        $result = $this->viewHelper->render();
        if ($expectFileReference === true) {
            self::assertInstanceOf(FileReference::class, $result);
        } else {
            self::assertNull($result);
        }
    }
}
