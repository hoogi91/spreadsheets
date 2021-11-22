<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Domain\ValueObject;

use Hoogi91\Spreadsheets\Domain\ValueObject\StylesheetValueObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class StylesheetValueObjectTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Domain\ValueObject
 */
class StylesheetValueObjectTest extends UnitTestCase
{
    private const DEFAULT_STYLES = [
        '.cell-type-b' => ['text-align' => 'center'], // BOOL
        '.cell-type-e' => ['text-align' => 'center'], // ERROR
        '.cell-type-f' => ['text-align' => 'right'], // FORMULA
        '.cell-type-inlineStr' => ['text-align' => 'left'], // INLINE
        '.cell-type-n' => ['text-align' => 'right'], // NUMERIC
        '.cell-type-s' => ['text-align' => 'left'], // STRING
    ];

    private const DEFAULT_STYLE_OUTPUT = '.cell-type-b {text-align:center}
.cell-type-e {text-align:center}
.cell-type-f {text-align:right}
.cell-type-inlineStr {text-align:left}
.cell-type-n {text-align:right}
.cell-type-s {text-align:left}
';

    private const DEFAULT_STYLE_WITH_IDENTIFIER = '#my-identifier .cell-type-b {text-align:center}
#my-identifier .cell-type-e {text-align:center}
#my-identifier .cell-type-f {text-align:right}
#my-identifier .cell-type-inlineStr {text-align:left}
#my-identifier .cell-type-n {text-align:right}
#my-identifier .cell-type-s {text-align:left}
';

    public function testStyleDataToCss(): void
    {
        $styles = StylesheetValueObject::create(self::DEFAULT_STYLES);
        self::assertEquals(self::DEFAULT_STYLE_OUTPUT, $styles->toCSS());
        // use casting
        self::assertEquals(self::DEFAULT_STYLE_OUTPUT, (string)$styles);
        // with identifier
        self::assertEquals(self::DEFAULT_STYLE_WITH_IDENTIFIER, $styles->toCSS('my-identifier'));

        // get inline css
        $styles = StylesheetValueObject::create(self::DEFAULT_STYLES['.cell-type-b']);
        self::assertEquals('text-align:center', $styles->toInlineCSS());
    }
}
