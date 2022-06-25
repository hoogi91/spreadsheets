<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\SiteFinder;
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

        if ($this->currentLocales === '' && is_int($GLOBALS['TSFE']->id)) {
            // happens, when Sites are used in TYPO3 9+
            $language = GeneralUtility::makeInstance(SiteFinder::class)
                ->getSiteByPageId($GLOBALS['TSFE']->id)
                ->getLanguageById(
                    GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id')
                );
            $this->currentLocales = $language->getLocale();
        }
    }

    /**
     * @param Cell $cell
     * @param callable|null $formatCallback
     *
     * @return string
     */
    public function getFormattedValue(Cell $cell, callable $formatCallback = null): string
    {
        if ($cell->getValue() === null) {
            return '';
        }

        $value = $cell->getValue();
        try {
            if ($value instanceof RichText) {
                $richTextValue = '';
                foreach ($value->getRichTextElements() as $element) {
                    $richTextValue .= $element instanceof Run
                        ? $this->getTextElementValue($element)
                        : $element->getText();
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
    private function getTextElementValue(Run $element): string
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
        return vsprintf(
            '<span style="%s">%s</span>',
            [
                $this->styleService->getStylesheetForRichTextElement($element)->toInlineCSS(),
                $textContent,
            ]
        );
    }

    /**
     * @param string|int|float $value
     * @param Cell $cell
     * @param callable|null $callback
     *
     * @return string
     */
    private function formatString($value, Cell $cell, callable $callback = null): string
    {
        $style = null;
        /** @var Spreadsheet|null $parent */
        $parent = $cell->getWorksheet()->getParent();
        $cellCollection = $parent !== null ? $parent->getCellXfCollection() : [];
        if (array_key_exists($cell->getXfIndex(), $cellCollection) === true) {
            $style = $parent->getCellXfByIndex($cell->getXfIndex());
        }

        // get cell style to find number format code
        if (is_numeric($value) && $style instanceof Style) {
            // check current locales and set them for converting numeric values
            if (!empty($this->currentLocales)) {
                $availableLocales = GeneralUtility::trimExplode(',', $this->currentLocales, true);
                $currentLocale = setlocale(LC_NUMERIC, '0');
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
                /** @psalm-suppress InvalidArgument */
                $value = NumberFormat::toFormattedString($value, $formatCode, $callback);
            }

            // reset locale to previous state
            if (!empty($this->currentLocales) && isset($currentLocale)) {
                setlocale(LC_NUMERIC, $currentLocale);
            }

            return (string)$value;
        }

        /** @psalm-suppress InvalidArgument */
        return (string)NumberFormat::toFormattedString($value, NumberFormat::FORMAT_GENERAL, $callback);
    }
}
