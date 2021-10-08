<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\ViewHelpers;

use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use Hoogi91\Spreadsheets\ViewHelpers\Cell\Value\FormattedViewHelper;
use Nimut\TestingFramework\TestCase\ViewHelperBaseTestcase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class FormattedViewHelperTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\ViewHelpers
 */
class FormattedViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var MockObject|FormattedViewHelper
     */
    protected $viewHelper;

    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(FormattedViewHelper::class)
            ->setMethods(['renderChildren'])
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    public function testRenderWithoutCell(): void
    {
        $this->viewHelper->initializeArguments();
        self::assertEmpty($this->viewHelper->render());
    }

    /**
     * @dataProvider cellProvider
     */
    public function testRender(string $expected, array $cellMock, string $target = null): void
    {
        $cellValue = $this->createConfiguredMock(CellDataValueObject::class, $cellMock);
        $this->setArgumentsUnderTest($this->viewHelper, ['cell' => $cellValue, 'target' => $target]);
        self::assertEquals($expected, $this->viewHelper->render());
    }

    /**
     * Based on 01_fixture.xlsx cell coordinates
     * @return array
     */
    public function cellProvider(): array
    {
        return [
            // special chars
            [
                '©™§∆',
                [
                    'getRenderedValue' => '©™§∆',
                    'isRichText' => false,
                    'isSuperscript' => false,
                    'isSubscript' => false,
                    'getHyperlink' => '',
                ],
            ],
            // hyperlink, hyperlink-title, target _self
            [
                '<a href="http://www.google.de/" target="_self" title="">Link</a>',
                [
                    'getRenderedValue' => 'Link',
                    'isRichText' => false,
                    'isSuperscript' => false,
                    'isSubscript' => false,
                    'getHyperlink' => 'http://www.google.de/',
                    'getHyperlinkTitle' => '',
                ],
                '_self'
            ],
            // richtext, superscript, subscript
            [
                '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
                [
                    'getRenderedValue' => '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
                    'isRichText' => true,
                    'isSuperscript' => false,
                    'isSubscript' => false,
                    'getHyperlink' => '',
                ]
            ],
            // superscript
            [
                '<sup>Hoch</sup>',
                [
                    'getRenderedValue' => 'Hoch',
                    'isRichText' => false,
                    'isSuperscript' => true,
                    'isSubscript' => false,
                    'getHyperlink' => '',
                ]
            ],
            // subscript
            [
                '<sub>2018</sub>',
                [
                    'getRenderedValue' => '2018',
                    'isRichText' => false,
                    'isSuperscript' => false,
                    'isSubscript' => true,
                    'getHyperlink' => '',
                ]
            ]
        ];
    }
}
