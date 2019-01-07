<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Enum;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Class HAlign
 * @package Hoogi91\Spreadsheets\Enum
 */
class HAlign
{
    const MAPPING = [
        Alignment::HORIZONTAL_LEFT              => 'left',
        Alignment::HORIZONTAL_RIGHT             => 'right',
        Alignment::HORIZONTAL_CENTER            => 'center',
        Alignment::HORIZONTAL_CENTER_CONTINUOUS => 'center',
        Alignment::HORIZONTAL_JUSTIFY           => 'justify',
    ];

    const MAPPING_HANDSONTABLE = [
        Alignment::HORIZONTAL_LEFT              => 'htLeft',
        Alignment::HORIZONTAL_RIGHT             => 'htRight',
        Alignment::HORIZONTAL_CENTER            => 'htCenter',
        Alignment::HORIZONTAL_CENTER_CONTINUOUS => 'htCenter',
        Alignment::HORIZONTAL_JUSTIFY           => 'htJustify',
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
        return '';
    }

    /**
     * @param string $value
     * @param string $dataType
     *
     * @return string
     */
    public static function mapHandsOnTable(string $value, $dataType = DataType::TYPE_NULL): string
    {
        if (array_key_exists($value, static::MAPPING_HANDSONTABLE)) {
            return static::MAPPING_HANDSONTABLE[$value];
        }

        // set default mapping based on type
        switch ($dataType) {
            case DataType::TYPE_BOOL:
            case DataType::TYPE_ERROR:
                return 'htCenter';
                break;
            case DataType::TYPE_FORMULA:
            case DataType::TYPE_NUMERIC:
                return 'htRight';
                break;
            default:
                break;
        }
        return '';
    }
}
