<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use Hoogi91\Spreadsheets\Domain\ValueObject\StylesheetValueObject;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style;

/**
 * Class StyleService
 * @package Hoogi91\Spreadsheets\Service
 */
class StyleService
{

    private const DEFAULT_STYLES = [
        '.cell-type-b' => ['text-align' => 'center'], // BOOL
        '.cell-type-e' => ['text-align' => 'center'], // ERROR
        '.cell-type-f' => ['text-align' => 'right'], // FORMULA
        '.cell-type-inlineStr' => ['text-align' => 'left'], // INLINE
        '.cell-type-n' => ['text-align' => 'right'], // NUMERIC
        '.cell-type-s' => ['text-align' => 'left'], // STRING
    ];

    /**
     * @var ValueMappingService
     */
    private $mappingService;

    /**
     * StyleService constructor.
     * @param ValueMappingService $mappingService
     */
    public function __construct(ValueMappingService $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @return StylesheetValueObject
     */
    public function getStylesheet(Spreadsheet $spreadsheet): StylesheetValueObject
    {
        // get style collection from spreadsheet
        $styleCollection = $spreadsheet->getCellXfCollection();
        if (empty($styleCollection)) {
            return StylesheetValueObject::create(self::DEFAULT_STYLES);
        }

        // get default styles
        $css = self::DEFAULT_STYLES;

        // extend styles with calculated styles from cell information
        foreach ($styleCollection as $index => $style) {
            $styles = array_merge(
                $this->getAlignmentStyles($style->getAlignment()),
                $this->getBorderStyles($style->getBorders()),
                $this->getFontStyles($style->getFont(), true),
                $this->getBackgroundStyles($style->getFill())
            );
            $css['.cell.cell-style-' . $index] = $styles;
        }

        return StylesheetValueObject::create($css);
    }

    public function getStylesheetForRichTextElement(Run $text): StylesheetValueObject
    {
        // extract font styles for current element
        $fontStyles = $this->getFontStyles($text->getFont(), true);
        return StylesheetValueObject::create($fontStyles);
    }

    /**
     * Create CSS style (\PhpOffice\PhpSpreadsheet\Style\Alignment).
     *
     * @param Style\Alignment $pStyle \PhpOffice\PhpSpreadsheet\Style\Alignment
     *
     * @return array
     */
    private function getAlignmentStyles(Style\Alignment $pStyle): array
    {
        $css = [];
        $css['vertical-align'] = $this->mappingService->convertValue('valign', $pStyle->getVertical(), 'baseline');

        $textAlign = $this->mappingService->convertValue('halign', $pStyle->getHorizontal());
        if (empty($textAlign) === true) {
            $css['text-align'] = $textAlign;
            if (in_array($textAlign, ['left', 'right'])) {
                $css['padding-' . $textAlign] = ((int)$pStyle->getIndent() * 9) . 'px';
            }
        }
        return $css;
    }

    /**
     * Create CSS style (Borders).
     *
     * @param Style\Borders $pStyle Borders
     *
     * @return array
     */
    private function getBorderStyles(Style\Borders $pStyle): array
    {
        $css = [];
        if ($pStyle->getBottom()->getBorderStyle() !== Style\Border::BORDER_NONE) {
            $css['border-bottom'] = $this->getBorderStyle($pStyle->getBottom());
        }
        if ($pStyle->getTop()->getBorderStyle() !== Style\Border::BORDER_NONE) {
            $css['border-top'] = $this->getBorderStyle($pStyle->getTop());
        }
        if ($pStyle->getLeft()->getBorderStyle() !== Style\Border::BORDER_NONE) {
            $css['border-left'] = $this->getBorderStyle($pStyle->getLeft());
        }
        if ($pStyle->getRight()->getBorderStyle() !== Style\Border::BORDER_NONE) {
            $css['border-right'] = $this->getBorderStyle($pStyle->getRight());
        }
        return $css;
    }

    /**
     * Create CSS style (Border).
     *
     * @param Style\Border $pStyle Border
     *
     * @return string
     */
    private function getBorderStyle(Style\Border $pStyle): string
    {
        // add !important to non-none border styles for merged cells
        $borderStyle = $this->mappingService->convertValue(
            'border-style',
            $pStyle->getBorderStyle(),
            '1px solid'
        );
        return $borderStyle . ' #' . $pStyle->getColor()->getRGB() . (($borderStyle === 'none') ? '' : ' !important');
    }

    /**
     * Create CSS style (\PhpOffice\PhpSpreadsheet\Style\Font).
     *
     * @param Style\Font $pStyle
     * @param bool $excludeFontFamilyAndSize
     *
     * @return array
     */
    private function getFontStyles(Style\Font $pStyle, $excludeFontFamilyAndSize = false): array
    {
        $css = [];
        $css['color'] = '#' . $pStyle->getColor()->getRGB();

        if ($pStyle->getBold() === true) {
            $css['font-weight'] = 'bold';
        }

        if ($pStyle->getItalic() === true) {
            $css['font-style'] = 'italic';
        }

        $css['text-decoration'] = '';
        if ($pStyle->getUnderline() !== Style\Font::UNDERLINE_NONE) {
            $css['text-decoration'] .= ' underline';
        } elseif ($pStyle->getStrikethrough() === true) {
            $css['text-decoration'] .= ' line-through';
        }
        $css['text-decoration'] = trim($css['text-decoration']);

        if ($excludeFontFamilyAndSize === false) {
            $css['font-family'] = '\'' . $pStyle->getName() . '\'';
            $css['font-size'] = $pStyle->getSize() . 'pt';
        }

        return array_filter($css);
    }

    /**
     * Create CSS style (Fill).
     *
     * @param Style\Fill $pStyle Fill
     *
     * @return array
     */
    private function getBackgroundStyles(Style\Fill $pStyle): array
    {
        $css = [];
        if ($pStyle->getFillType() !== Style\Fill::FILL_NONE) {
            $css['background-color'] = '#' . $pStyle->getStartColor()->getRGB();
        }
        return $css;
    }
}
