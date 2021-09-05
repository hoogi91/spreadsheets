<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\ViewHelpers;

use Hoogi91\Spreadsheets\Tests\Unit\FileRepositoryMockTrait;
use Hoogi91\Spreadsheets\ViewHelpers\Cell\Value\FormattedViewHelper;
use Hoogi91\Spreadsheets\ViewHelpers\Reader\Sheet\TitleViewHelper;
use Nimut\TestingFramework\TestCase\ViewHelperBaseTestcase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class TitleViewHelperTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\ViewHelpers
 */
class TitleViewHelperTest extends ViewHelperBaseTestcase
{
    use FileRepositoryMockTrait;

    /**
     * @var MockObject|FormattedViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(TitleViewHelper::class)
            ->setMethods(['renderChildren'])
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    public function testRenderWithoutFile(): void
    {
        $this->viewHelper->initializeArguments();
        self::assertEmpty($this->viewHelper->render());
    }

    /**
     * @testWith ["Fixture1", 0]
     *           ["Fixture2", 1]
     *           ["", 100]
     */
    public function testRender(string $expected, int $sheetIndex): void
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            ['file' => $this->getFileReferenceMock('01_fixture.xlsx'), 'index' => $sheetIndex]
        );
        self::assertEquals($expected, $this->viewHelper->render());
    }
}
