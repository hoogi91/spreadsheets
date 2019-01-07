<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Hoogi91\Spreadsheets\Enum\BorderStyle;
use Hoogi91\Spreadsheets\Enum\HAlign;
use Hoogi91\Spreadsheets\Enum\VAlign;
use Hoogi91\Spreadsheets\Traits\SheetIndexTrait;

/**
 * Class StyleService
 * @package Hoogi91\Spreadsheets\Service
 */
class StyleService
{
    use SheetIndexTrait;

    /**
     * normally the ID of the html element (wrapped around all styles)
     *
     * @var string
     */
    protected $identifier = '';

    /**
     * @var array
     */
    protected $styles = [
        '.cell-type-b'         => ['text-align' => 'center'], // BOOL
        '.cell-type-e'         => ['text-align' => 'center'], // ERROR
        '.cell-type-f'         => ['text-align' => 'right'], // FORMULA
        '.cell-type-inlineStr' => ['text-align' => 'left'], // INLINE
        '.cell-type-n'         => ['text-align' => 'right'], // NUMERIC
        '.cell-type-s'         => ['text-align' => 'left'], // STRING
    ];

    /**
     * RangeService constructor.
     *
     * @param Spreadsheet $spreadsheet
     */
    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->setSpreadsheet($spreadsheet);
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return StyleService
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @param array $references
     *
     * @return array
     */
    public function getCellStyleIndexesFromReferences(array $references)
    {
        if (empty($references)) {
            return [];
        }

        try {
            $result = [];
            $sheet = $this->getSpreadsheet()->getActiveSheet();
            foreach ($references as $cellRef) {
                $result[] = $sheet->getCell($cellRef)->getXfIndex();
            }
            return array_unique($result);
        } catch (SpreadsheetException $e) {
            // return empty cell style informations if spreadsheet couldn't be loaded
        }
        return [];
    }

    /**
     * Create CSS style (\PhpOffice\PhpSpreadsheet\Style\Alignment).
     *
     * @param Alignment $pStyle \PhpOffice\PhpSpreadsheet\Style\Alignment
     *
     * @return array
     */
    public function getAlignmentStyles(Alignment $pStyle): array
    {
        $css = [];
        $css['vertical-align'] = VAlign::map($pStyle->getVertical());
        if ($textAlign = HAlign::map($pStyle->getHorizontal())) {
            $css['text-align'] = $textAlign;
            if (in_array($textAlign, ['left', 'right'])) {
                $css['padding-' . $textAlign] = (string)((int)$pStyle->getIndent() * 9) . 'px';
            }
        }
        return $css;
    }

    /**
     * Create CSS style (Borders).
     *
     * @param Borders $pStyle Borders
     *
     * @return array
     */
    public function getBorderStyles(Borders $pStyle): array
    {
        $css = [];
        if ($pStyle->getBottom()->getBorderStyle() !== Border::BORDER_NONE) {
            $css['border-bottom'] = $this->getBorderStyle($pStyle->getBottom());
        }
        if ($pStyle->getTop()->getBorderStyle() !== Border::BORDER_NONE) {
            $css['border-top'] = $this->getBorderStyle($pStyle->getTop());
        }
        if ($pStyle->getLeft()->getBorderStyle() !== Border::BORDER_NONE) {
            $css['border-left'] = $this->getBorderStyle($pStyle->getLeft());
        }
        if ($pStyle->getRight()->getBorderStyle() !== Border::BORDER_NONE) {
            $css['border-right'] = $this->getBorderStyle($pStyle->getRight());
        }
        return $css;
    }

    /**
     * Create CSS style (Border).
     *
     * @param Border $pStyle Border
     *
     * @return string
     */
    protected function getBorderStyle(Border $pStyle): string
    {
        // add !important to non-none border styles for merged cells
        $borderStyle = BorderStyle::map($pStyle->getBorderStyle());
        return $borderStyle . ' #' . $pStyle->getColor()->getRGB() . (($borderStyle == 'none') ? '' : ' !important');
    }

    /**
     * Create CSS style (\PhpOffice\PhpSpreadsheet\Style\Font).
     *
     * @param Font $pStyle
     * @param bool $excludeFontFamilyAndSize
     *
     * @return array
     */
    public function getFontStyles(Font $pStyle, $excludeFontFamilyAndSize = false): array
    {
        $css = [];

        if ($pStyle->getBold()) {
            $css['font-weight'] = 'bold';
        }

        if ($pStyle->getUnderline() != Font::UNDERLINE_NONE && $pStyle->getStrikethrough()) {
            $css['text-decoration'] = 'underline line-through';
        } elseif ($pStyle->getUnderline() != Font::UNDERLINE_NONE) {
            $css['text-decoration'] = 'underline';
        } elseif ($pStyle->getStrikethrough()) {
            $css['text-decoration'] = 'line-through';
        }

        if ($pStyle->getItalic()) {
            $css['font-style'] = 'italic';
        }

        $css['color'] = '#' . $pStyle->getColor()->getRGB();

        if ($excludeFontFamilyAndSize === false) {
            $css['font-family'] = '\'' . $pStyle->getName() . '\'';
            $css['font-size'] = $pStyle->getSize() . 'pt';
        }
        return $css;
    }

    /**
     * Create CSS style (Fill).
     *
     * @param Fill $pStyle Fill
     *
     * @return array
     */
    public function getBackgroundStyles(Fill $pStyle): array
    {
        $css = [];
        if ($pStyle->getFillType() !== Fill::FILL_NONE) {
            $css['background-color'] = '#' . $pStyle->getStartColor()->getRGB();
        }
        return $css;
    }

    /**
     * Takes array where of CSS properties / values and converts to CSS string.
     *
     * @param array $styles
     *
     * @return string
     */
    public function assembleStyles($styles = []): string
    {
        if (empty($styles)) {
            return '';
        }

        $pairs = [];
        foreach ($styles as $property => $value) {
            $pairs[] = $property . ':' . $value;
        }
        return implode(';', $pairs);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        // get default styles
        $css = $this->styles;

        // get style collection from spreadsheet
        $styleCollection = $this->getSpreadsheet()->getCellXfCollection();
        if (empty($styleCollection) || !is_array($styleCollection)) {
            return $css;
        }

        // extend styles with calculated styles from cell informations
        foreach ($styleCollection as $index => $style) {
            $styles = array_merge(
                $this->getAlignmentStyles($style->getAlignment()),
                $this->getBorderStyles($style->getBorders()),
                $this->getFontStyles($style->getFont(), true),
                $this->getBackgroundStyles($style->getFill())
            );
            $css['.cell.cell-style-' . $index] = $styles;
        }

        return $css;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $styles = $this->toArray();
        if (empty($styles)) {
            return '';
        }

        // write all styles with table selector prefix
        $content = '';
        $htmlIdentifier = $this->getIdentifier();
        foreach ($styles as $styleName => $styleDefinition) {
            if ($styleName != 'html') {
                if (!empty($htmlIdentifier)) {
                    $content .= '#' . $htmlIdentifier . ' ' . $styleName . ' { ' . $this->assembleStyles($styleDefinition) . ' }' . PHP_EOL;
                } else {
                    $content .= $styleName . ' { ' . $this->assembleStyles($styleDefinition) . ' }' . PHP_EOL;
                }
            }
        }
        return $content;
    }
}
