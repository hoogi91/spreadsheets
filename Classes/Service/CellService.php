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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Hoogi91\Spreadsheets\Traits\SheetIndexTrait;

/**
 * Class CellService
 * @package Hoogi91\Spreadsheets\Service
 */
class CellService
{
    use SheetIndexTrait;

    /**
     * @var StyleService
     */
    protected $styleService;

    /**
     * @var array
     */
    protected $typoscriptConfig;

    /**
     * @var string
     */
    protected $currentLocales = '';

    /**
     * CellService constructor.
     *
     * @param Spreadsheet $spreadsheet
     */
    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->setSpreadsheet($spreadsheet);
        $this->styleService = GeneralUtility::makeInstance(StyleService::class, $this->getSpreadsheet());

        // evaluate typoscript configuration and locales
        $this->typoscriptConfig = $GLOBALS['TSFE']->config;
        $this->currentLocales = $this->typoscriptConfig['config']['locale_all'];
    }

    /**
     * @param Cell     $cell
     * @param bool     $calculate Should formulas be calculated?
     * @param bool     $format    Should formatting be applied to cell values?
     * @param callable $formatCallback
     *
     * @return string|int|float
     */
    public function getValue(Cell $cell, bool $calculate = true, bool $format = true, callable $formatCallback = null)
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
            } elseif ($calculate === true) {
                $value = $cell->getCalculatedValue();
            }
        } catch (SpreadsheetException $e) {
            // if something wents wrong while evaluating calculated or rich-text value we fallback to raw value
        }

        if ($format === true) {
            $value = $this->formatString($value, $cell, $formatCallback);
        }

        return $value;
    }

    /**
     * @param Run $element
     *
     * @return string
     */
    protected function getTextElementValue($element)
    {
        // evaluate text content and check if it is superscript or subscript
        $textContent = $element->getText();
        if ($element->getFont()->getSuperscript()) {
            $textContent = sprintf('<sup>%s</sup>', $textContent);
        } elseif ($element->getFont()->getSubscript()) {
            $textContent = sprintf('<sub>%s</sub>', $textContent);
        }

        // extract font styles for current element
        $fontStyles = $this->styleService->getFontStyles($element->getFont(), true);

        // create span to add font styles and insert textual content inside
        return vsprintf('<span style="%s">%s</span>', [
            $this->styleService->assembleStyles($fontStyles),
            $textContent,
        ]);
    }

    /**
     * @param string|int|float $value
     * @param Cell             $cell
     * @param callable         $callback
     *
     * @return string|int|float
     */
    protected function formatString($value, Cell $cell, callable $callback = null)
    {
        // get cell style to find number format code
        $style = $this->getSpreadsheet()->getCellXfByIndex($cell->getXfIndex());
        if (is_numeric($value) && $style instanceof Style) {
            // check current locales and set them for converting numeric values
            if (!empty($this->currentLocales)) {
                $availableLocales = GeneralUtility::trimExplode(',', $this->currentLocales, true);
                $currentLocale = setlocale(LC_NUMERIC, 0);
                setlocale(LC_NUMERIC, ...$availableLocales);
            }

            // format number in respoect of current locale
            $value = NumberFormat::toFormattedString($value, $style->getNumberFormat()->getFormatCode(), $callback);

            // reset locale to previous state
            if (!empty($this->currentLocales) && isset($currentLocale)) {
                setlocale(LC_NUMERIC, $currentLocale);
            }

            return $value;
        }

        return NumberFormat::toFormattedString($value, NumberFormat::FORMAT_GENERAL, $callback);
    }

}
