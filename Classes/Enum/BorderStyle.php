<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Enum;

use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Class BorderStyle
 * @package Hoogi91\Spreadsheets\Enum
 */
class BorderStyle
{
    const MAPPING = [
        Border::BORDER_NONE             => 'none',
        Border::BORDER_DASHDOT          => '1px dashed',
        Border::BORDER_DASHDOTDOT       => '1px dotted',
        Border::BORDER_DASHED           => '1px dashed',
        Border::BORDER_DOTTED           => '1px dotted',
        Border::BORDER_DOUBLE           => '3px double',
        Border::BORDER_HAIR             => '1px solid',
        Border::BORDER_MEDIUM           => '2px solid',
        Border::BORDER_MEDIUMDASHDOT    => '2px dashed',
        Border::BORDER_MEDIUMDASHDOTDOT => '2px dotted',
        Border::BORDER_MEDIUMDASHED     => '2px dashed',
        Border::BORDER_SLANTDASHDOT     => '2px dashed',
        Border::BORDER_THICK            => '3px solid',
        Border::BORDER_THIN             => '1px solid',
    ];

    /**
     * @param string $value
     *
     * @return string
     */
    public static function map(string $value): string
    {
        if (array_key_exists($value, static::MAPPING)) {
            return static::MAPPING[$value];
        }
        return '1px solid';
    }
}
