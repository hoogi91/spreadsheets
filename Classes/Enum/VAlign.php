<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Enum;

use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Class VAlign
 * @package Hoogi91\Spreadsheets\Enum
 */
class VAlign
{
    const MAPPING = [
        Alignment::VERTICAL_BOTTOM  => 'bottom',
        Alignment::VERTICAL_TOP     => 'top',
        Alignment::VERTICAL_CENTER  => 'middle',
        Alignment::VERTICAL_JUSTIFY => 'middle',
    ];

    const MAPPING_HANDSONTABLE = [
        Alignment::VERTICAL_BOTTOM  => 'htBottom',
        Alignment::VERTICAL_TOP     => 'htTop',
        Alignment::VERTICAL_CENTER  => 'htMiddle',
        Alignment::VERTICAL_JUSTIFY => 'htMiddle',
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
        return 'baseline';
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public static function mapHandsOnTable(string $value): string
    {
        if (array_key_exists($value, static::MAPPING_HANDSONTABLE)) {
            return static::MAPPING_HANDSONTABLE[$value];
        }
        return '';
    }
}
