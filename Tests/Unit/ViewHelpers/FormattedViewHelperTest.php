<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\ViewHelpers;

use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use Hoogi91\Spreadsheets\Service\CellService;
use Hoogi91\Spreadsheets\Service\StyleService;
use Hoogi91\Spreadsheets\Service\ValueMappingService;
use Hoogi91\Spreadsheets\ViewHelpers\Cell\Value\FormattedViewHelper;
use Nimut\TestingFramework\TestCase\ViewHelperBaseTestcase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
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
        $this->spreadsheet = (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');
    }

    public function testRenderWithoutCell(): void
    {
        $this->viewHelper->initializeArguments();
        self::assertEmpty($this->viewHelper->render());
    }

    /**
     * @dataProvider cellProvider
     */
    public function testRender(string $expected, string $coordinate, string $target = null): void
    {
        $cellService = new CellService(new StyleService(new ValueMappingService()));

        $cell = $this->spreadsheet->getActiveSheet()->getCell($coordinate);
        $cellValue = CellDataValueObject::create($cell, $cellService->getFormattedValue($cell));
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
            ['©™§∆', 'A4'],
            // hyperlink, hyperlink-title, target _self
            [
                '<a href="http://www.google.de/" target="_self" title="">Link</a>',
                'D4',
                '_self'
            ],
            // richtext, superscript, subscript
            [
                '<span style="color:#000000"><sup>Hoch</sup></span><span style="color:#000000"> Test </span><span style="color:#000000"><sub>Tief</sub></span>',
                'D5'
            ],
            // superscript
            [
                '<sup>Hoch</sup>',
                'E5'
            ],
            // subscript
            [
                '<sub>2018</sub>',
                'E6'
            ]
        ];
    }
}
