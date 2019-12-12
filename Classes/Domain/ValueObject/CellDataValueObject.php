<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\ValueObject;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

/**
 * Class CellDataValueObject
 * @package Hoogi91\Spreadsheets\Domain\ValueObject
 */
class CellDataValueObject
{
    /**
     * @var Cell
     */
    private $cell;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $formattedValue;

    /**
     * @var int
     */
    private $rowspan;

    /**
     * @var int
     */
    private $colspan;

    /**
     * @var int
     */
    private $styleIndex;

    /**
     * @var array
     */
    private $additionalStyleIndexes;

    /**
     * @var bool
     */
    private $isRichText = false;

    /**
     * @var bool
     */
    private $superscript = false;

    /**
     * @var bool
     */
    private $subscript = false;

    /**
     * @var string
     */
    private $hyperlink = '';

    /**
     * @var string
     */
    private $hyperlinkTitle = '';

    /**
     * CellDataValueObject constructor.
     *
     * @param Cell $cell
     *
     * @param string $formattedValue
     * @param int $rowspan
     * @param int $colspan
     * @param array $additionalStyles
     * @throws SpreadsheetException
     */
    public function __construct(
        Cell $cell,
        string $formattedValue,
        int $rowspan = 0,
        int $colspan = 0,
        array $additionalStyles = []
    ) {
        $this->cell = $cell;
        $this->type = $cell->getDataType();
        $this->formattedValue = $formattedValue;
        $this->rowspan = $rowspan;
        $this->colspan = $colspan;
        $this->styleIndex = $cell->getXfIndex();
        $this->additionalStyleIndexes = $additionalStyles;

        // check for super- and subscript
        if ($cell->getValue() instanceof RichText) {
            // set rich text option true to ignore styling - cause value has integrated styling
            $this->isRichText = true;
        } else {
            $this->superscript = $cell->getStyle()->getFont()->getSuperscript();
            $this->subscript = $cell->getStyle()->getFont()->getSubscript();
        }

        // Hyperlink?
        if ($cell->hasHyperlink() === true && $cell->getHyperlink()->isInternal() === false) {
            $this->hyperlink = $cell->getHyperlink()->getUrl();
            $this->hyperlinkTitle = $cell->getHyperlink()->getTooltip();
        }
    }

    /**
     * @param Cell $cell
     * @param string $formattedValue
     * @param int $rowspan
     * @param int $colspan
     * @param array $additionalStyles
     * @return CellDataValueObject
     * @throws SpreadsheetException
     */
    public static function create(
        Cell $cell,
        string $formattedValue,
        int $rowspan = 0,
        int $colspan = 0,
        array $additionalStyles = []
    ): CellDataValueObject {
        return new self($cell, $formattedValue, $rowspan, $colspan, $additionalStyles);
    }

    /**
     * @return Cell
     */
    public function getCell(): Cell
    {
        return $this->cell;
    }

    /**
     * @return string|int|float|mixed
     * @throws SpreadsheetException
     */
    public function getCalculatedValue()
    {
        return $this->cell->getCalculatedValue();
    }

    /**
     * @return string
     */
    public function getFormattedValue(): string
    {
        return $this->formattedValue;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getStyleIndex(): int
    {
        return $this->styleIndex;
    }

    /**
     * @return array
     */
    public function getAdditionalStyleIndexes(): array
    {
        return $this->additionalStyleIndexes;
    }

    /**
     * @return bool
     */
    public function isRichText(): bool
    {
        return $this->isRichText;
    }

    /**
     * @return bool
     */
    public function isSuperscript(): bool
    {
        return $this->superscript;
    }

    /**
     * @return bool
     */
    public function isSubscript(): bool
    {
        return $this->subscript;
    }

    /**
     * @return string
     */
    public function getHyperlink(): string
    {
        return $this->hyperlink;
    }

    /**
     * @return string
     */
    public function getHyperlinkTitle(): string
    {
        return $this->hyperlinkTitle;
    }

    /**
     * @return int
     */
    public function getRowspan(): int
    {
        return $this->rowspan;
    }

    /**
     * @return int
     */
    public function getColspan(): int
    {
        return $this->colspan;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        $cellClass = 'cell';
        $cellClass .= sprintf(' cell-type-%s', $this->getType());
        $cellClass .= sprintf(' cell-style-%d', $this->getStyleIndex());

        foreach ($this->getAdditionalStyleIndexes() as $indexes) {
            $cellClass .= sprintf(' cell-style-%d', $indexes);
        }
        return $cellClass;
    }
}
