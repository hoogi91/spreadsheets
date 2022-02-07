<?php

namespace Hoogi91\Spreadsheets\Tests\Functional\ViewHelpers;

use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;

class CellRenderViewHelperTest extends AbstractViewHelperTestCase
{
    private const CELL_DEFAULT_CONTENT = '<td%s>content</td>';
    private const CELL_HEADER_CONTENT = '<th%s>content</th>';

    /**
     * @dataProvider cellProvider
     */
    public function testRender(
        string $expectedAttributes,
        ?array $cellMock,
        ?string $scope = null,
        bool $isHeader = false
    ): void {
        if ($cellMock !== null) {
            $cellValue = $this->createConfiguredMock(CellDataValueObject::class, $cellMock);
        }

        self::assertEquals(
            sprintf(
                $isHeader === false ? self::CELL_DEFAULT_CONTENT : self::CELL_HEADER_CONTENT,
                $expectedAttributes !== '' ? ' ' . $expectedAttributes : ''
            ),
            $this->getView(
                '<test:cell.render cell="{cell}" scope="{scope}" isHeader="{isHeader}">content</test:cell.render>',
                ['cell' => $cellValue ?? null, 'scope' => $scope ?? null, 'isHeader' => $isHeader]
            )->render()
        );
    }

    /**
     * @dataProvider cellProvider
     */
    public function testRenderAsHeader(string $expectedAttributes, ?array $cellMock): void
    {
        $this->testRender($expectedAttributes, $cellMock, null, true);
    }

    /**
     * @dataProvider cellProvider
     */
    public function testRenderWithRowScope(string $expectedAttributes, ?array $cellMock): void
    {
        $this->testRender($expectedAttributes, $cellMock, 'row');
    }

    /**
     * @dataProvider cellProvider
     */
    public function testRenderAsHeaderWithRowScope(string $expectedAttributes, ?array $cellMock): void
    {
        $expectedAttributes = trim('scope="row" ' . $expectedAttributes);
        $this->testRender($expectedAttributes, $cellMock, 'row', true);
    }

    /**
     * @dataProvider cellProvider
     */
    public function testRenderAsHeaderWithColScope(string $expectedAttributes, ?array $cellMock): void
    {
        $expectedAttributes = trim('scope="col" ' . $expectedAttributes);
        $this->testRender($expectedAttributes, $cellMock, 'col', true);
    }

    /**
     * Based on 01_fixture.xlsx cell coordinates
     * @return array
     */
    public function cellProvider(): array
    {
        return [
            'no cell' => ['', null],
            'no attributes' => [
                '',
                ['getClass' => '', 'getRowspan' => 0, 'getColspan' => 0]
            ],
            'only class' => [
                'class="cell"',
                ['getClass' => 'cell', 'getRowspan' => 0, 'getColspan' => 0]
            ],
            'only rowspan' => [
                'rowspan="1"',
                ['getClass' => '', 'getRowspan' => 1, 'getColspan' => 0]
            ],
            'only colspan' => [
                'colspan="1"',
                ['getClass' => '', 'getRowspan' => 0, 'getColspan' => 1]
            ],
            'class + rowspan' => [
                'class="cell" rowspan="2"',
                ['getClass' => 'cell', 'getRowspan' => 2, 'getColspan' => 0]
            ],
            'class + colspan' => [
                'class="cell" colspan="2"',
                ['getClass' => 'cell', 'getRowspan' => 0, 'getColspan' => 2]
            ],
            'rowspan + colspan' => [
                'rowspan="3" colspan="3"',
                ['getClass' => '', 'getRowspan' => 3, 'getColspan' => 3]
            ],
            'class + rowspan + colspan' => [
                'class="cell" rowspan="4" colspan="4"',
                ['getClass' => 'cell', 'getRowspan' => 4, 'getColspan' => 4]
            ],
        ];
    }
}
