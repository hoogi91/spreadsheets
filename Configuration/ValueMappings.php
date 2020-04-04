<?php

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

return [
    'border-style' => [
        Border::BORDER_NONE => 'none',
        Border::BORDER_DASHDOT => '1px dashed',
        Border::BORDER_DASHDOTDOT => '1px dotted',
        Border::BORDER_DASHED => '1px dashed',
        Border::BORDER_DOTTED => '1px dotted',
        Border::BORDER_DOUBLE => '3px double',
        Border::BORDER_HAIR => '1px solid',
        Border::BORDER_MEDIUM => '2px solid',
        Border::BORDER_MEDIUMDASHDOT => '2px dashed',
        Border::BORDER_MEDIUMDASHDOTDOT => '2px dotted',
        Border::BORDER_MEDIUMDASHED => '2px dashed',
        Border::BORDER_SLANTDASHDOT => '2px dashed',
        Border::BORDER_THICK => '3px solid',
        Border::BORDER_THIN => '1px solid',
    ],
    'halign' => [
        Alignment::HORIZONTAL_LEFT => 'left',
        Alignment::HORIZONTAL_RIGHT => 'right',
        Alignment::HORIZONTAL_CENTER => 'center',
        Alignment::HORIZONTAL_CENTER_CONTINUOUS => 'center',
        Alignment::HORIZONTAL_JUSTIFY => 'justify',
    ],
    'halign-backend' => [
        Alignment::HORIZONTAL_LEFT => null, // default value => see CSS
        Alignment::HORIZONTAL_RIGHT => 'r',
        Alignment::HORIZONTAL_CENTER => 'c',
        Alignment::HORIZONTAL_CENTER_CONTINUOUS => 'c',
        Alignment::HORIZONTAL_JUSTIFY => 'j',
    ],
    'halign-backend-datatype' => [
        DataType::TYPE_BOOL => 'c',
        DataType::TYPE_ERROR => 'c',
        DataType::TYPE_FORMULA => 'r',
        DataType::TYPE_NUMERIC => 'r',
    ],
    'valign' => [
        Alignment::VERTICAL_BOTTOM => 'bottom',
        Alignment::VERTICAL_TOP => 'top',
        Alignment::VERTICAL_CENTER => 'middle',
        Alignment::VERTICAL_JUSTIFY => 'middle',
    ],
    'valign-backend' => [
        Alignment::VERTICAL_BOTTOM => null, // default value => see CSS
        Alignment::VERTICAL_TOP => 't',
        Alignment::VERTICAL_CENTER => 'm',
        Alignment::VERTICAL_JUSTIFY => 'm',
    ],
];
