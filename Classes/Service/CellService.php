<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use const LC_NUMERIC;

class CellService
{
    private string $currentLocales;

    public function __construct(private readonly StyleService $styleService)
    {
        /** @var SiteLanguage|null $language */
        $language = $this->getRequest()->getAttribute('language');
        if ((new Typo3Version())->getMajorVersion() > 11) {
            $this->currentLocales = $language?->getLocale()->getLanguageCode() ?? '';

            return;
        }

        // @codeCoverageIgnoreStart
        // This block is for legacy support and will not be tested during test run with coverage
        $this->currentLocales = $language?->getLocale() ?? '';
        // @codeCoverageIgnoreEnd
    }

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    public function getFormattedValue(Cell $cell): string
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
        } catch (SpreadsheetException) {
            // if something went wrong while evaluating calculated or rich-text value we fallback to raw value
        }

        return $this->formatString($value, $cell);
    }

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

    private function formatString(mixed $value, Cell $cell): string
    {
        $style = null;
        $parent = $cell->getWorksheet()->getParent();
        $cellCollection = $parent?->getCellXfCollection() ?? [];
        if (array_key_exists($cell->getXfIndex(), $cellCollection) === true) {
            $style = $parent?->getCellXfByIndex($cell->getXfIndex());
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
            $formatCode = str_replace('\\ ', ' ', $style->getNumberFormat()->getFormatCode() ?? '');

            // check for scientific format and do better formatting than NumberFormat class
            preg_match('/(0+)(\\.?)(0*)E[+-]0/i', $formatCode, $matches);
            // extract count of decimals and use it as print argument
            // otherwise do normal format logic with given format code
            $value = isset($matches[3]) && $matches[3] !== ''
                ? sprintf('%5.' . strlen($matches[3]) . 'E', $value)
                : NumberFormat::toFormattedString($value, $formatCode);

            // reset locale to previous state
            if (isset($currentLocale) && is_string($currentLocale)) {
                setlocale(LC_NUMERIC, $currentLocale);
            }

            return $value;
        }

        /** @var null|bool|float|int|RichText|string $value */
        return NumberFormat::toFormattedString($value, NumberFormat::FORMAT_GENERAL);
    }
}
