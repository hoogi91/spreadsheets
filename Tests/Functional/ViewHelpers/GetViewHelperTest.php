<?php

namespace Hoogi91\Spreadsheets\Tests\Functional\ViewHelpers;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Tests\Unit\DsnProviderTrait;
use Throwable;

class GetViewHelperTest extends AbstractViewHelperTestCase
{
    use DsnProviderTrait;

    public function testRenderWithoutSubject(): void
    {
        self::assertEmpty($this->getView('<test:value.get subject=""/>')->render());
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
        $view = $this->getView('<test:value.get subject="{dsn}"/>', ['dsn' => $dsn]);
        if (empty($dsn)) {
            self::assertEmpty($view->render());
        } elseif (is_subclass_of($exceptionClassOrFinalDsn, Throwable::class) === true) {
            $this->expectException($exceptionClassOrFinalDsn);
            $view->render();
        } else {
            $dsnValue = $view->render();
            self::assertInstanceOf(DsnValueObject::class, $dsnValue);
            self::assertEquals($exceptionClassOrFinalDsn, $dsnValue->getDsn());
        }
    }
}
