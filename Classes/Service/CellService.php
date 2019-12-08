<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CellService
 * @package Hoogi91\Spreadsheets\Service
 */
class CellService
{
    /**
     * @var StyleService
     */
    private $styleService;

    /**
     * @var string
     */
    private $currentLocales;

    /**
     * CellService constructor
     * @param StyleService $styleService
     */
    public function __construct(StyleService $styleService)
    {
        $this->styleService = $styleService;
        $this->currentLocales = $GLOBALS['TSFE']->config['config']['locale_all'] ?? '';
    }

    /**
     * @param Cell $cell
     * @param callable $formatCallback
     *
     * @return string|int|float
     */
    public function getValue(Cell $cell, callable $formatCallback = null)
    {
        if ($cell->getValue() === null) {
            return '';
        }

        $value = $cell->getValue();
        try {
            if ($value instanceof RichText) {
                $richTextValue = '';
                foreach ($value->getRichTextElements() as $element) {
                    if ($element instanceof Run) {
                        $richTextValue .= $this->getTextElementValue($element);
                    } else {
                        $richTextValue .= $element->getText();
                    }
                }
                $value = $richTextValue;
            } else {
                $value = $cell->getCalculatedValue();
            }
        } catch (SpreadsheetException $e) {
            // if something went wrong while evaluating calculated or rich-text value we fallback to raw value
        }

        return $this->formatString($value, $cell, $formatCallback);
    }

    /**
     * @param Run $element
     *
     * @return string
     */
    private function getTextElementValue($element): string
    {
        // evaluate text content and check if it is superscript or subscript
        $textContent = $element->getText();
        if ($element->getFont() === null) {
            return $textContent;
        }

        if ($element->getFont()->getSuperscript()) {
            $textContent = sprintf('<sup>%s</sup>', $textContent);
        } elseif ($element->getFont()->getSubscript()) {
            $textContent = sprintf('<sub>%s</sub>', $textContent);
        }

        // create span to add font styles and insert textual content inside
        return vsprintf('<span style="%s">%s</span>', [
            $this->styleService->getStylesheetForRichTextElement($element)->toInlineCSS(),
            $textContent,
        ]);
    }

    /**
     * @param string|int|float $value
     * @param Cell $cell
     * @param callable $callback
     *
     * @return string|int|float
     */
    private function formatString($value, Cell $cell, callable $callback = null)
    {
        // get cell style to find number format code
        $style = $cell->getWorksheet()->getParent()->getCellXfByIndex($cell->getXfIndex());
        if (is_numeric($value) && $style instanceof Style) {
            // check current locales and set them for converting numeric values
            if (!empty($this->currentLocales)) {
                $availableLocales = GeneralUtility::trimExplode(',', $this->currentLocales, true);
                $currentLocale = setlocale(LC_NUMERIC, 0);
                setlocale(LC_NUMERIC, ...$availableLocales);
            }

            // remove escaped whitespaces from format code to get correct formatted numbers
            $formatCode = str_replace('\\ ', ' ', $style->getNumberFormat()->getFormatCode());

            // check for scientific format and do better formatting than NumberFormat class
            preg_match('/(0+)(\\.?)(0*)E[+-]0/i', $formatCode, $matches);
            if (isset($matches[3]) && $matches[3] !== '') {
                // extract count of decimals and use it as print argument
                $value = sprintf('%5.' . strlen($matches[3]) . 'E', $value);
            } else {
                // otherwise do normal format logic with given format code
                $value = NumberFormat::toFormattedString($value, $formatCode, $callback);
            }

            // reset locale to previous state
            if (!empty($this->currentLocales) && isset($currentLocale)) {
                setlocale(LC_NUMERIC, $currentLocale);
            }

            return $value;
        }

        return NumberFormat::toFormattedString($value, NumberFormat::FORMAT_GENERAL, $callback);
    }
}
