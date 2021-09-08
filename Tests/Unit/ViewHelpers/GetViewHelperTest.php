<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\ViewHelpers;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Tests\Unit\DsnProviderTrait;
use Hoogi91\Spreadsheets\Tests\Unit\FileRepositoryMockTrait;
use Hoogi91\Spreadsheets\ViewHelpers\Cell\Value\FormattedViewHelper;
use Hoogi91\Spreadsheets\ViewHelpers\Value\GetViewHelper;
use Nimut\TestingFramework\TestCase\ViewHelperBaseTestcase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GetViewHelperTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\ViewHelpers
 */
class GetViewHelperTest extends ViewHelperBaseTestcase
{
    use DsnProviderTrait;
    use FileRepositoryMockTrait;

    /**
     * @var MockObject|FormattedViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(GetViewHelper::class)
            ->setMethods(['renderChildren'])
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getContainerMock();
        $container->method('get')->willReturn($this->getFileRepositoryMock());
        GeneralUtility::setContainer($container);
    }

    public function testRenderWithoutSubject(): void
    {
        $this->viewHelper->initializeArguments();
        self::assertEmpty($this->viewHelper->render());
    }

    /**
     * @dataProvider legacyProvider
     */
    public function testRenderLegacy(string $dsn, string $exceptionClassOrFinalDsn): void
    {
        $this->testRender($dsn, $exceptionClassOrFinalDsn);
    }

    /**
     * @dataProvider dsnProvider
     */
    public function testRender(string $dsn, string $exceptionClassOrFinalDsn): void
    {
        $this->setArgumentsUnderTest($this->viewHelper, ['subject' => $dsn]);

        if (empty($dsn)) {
            self::assertEmpty($this->viewHelper->render());
        } elseif (is_subclass_of($exceptionClassOrFinalDsn, Throwable::class) === true) {
            $this->expectException($exceptionClassOrFinalDsn);
            $this->viewHelper->render();
        } else {
            $dsn = $this->viewHelper->render();
            self::assertInstanceOf(DsnValueObject::class, $dsn);
            self::assertEquals($exceptionClassOrFinalDsn, $dsn->getDsn());
        }
    }
}
