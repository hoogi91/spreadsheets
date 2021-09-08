<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\ValueObject;

use JsonSerializable;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

/**
 * Class CellDataValueObject
 * @package Hoogi91\Spreadsheets\Domain\ValueObject
 */
class CellDataValueObject implements JsonSerializable
{

    /**
     * @var string
     */
    private $calculatedValue;

    /**
     * @var string
     */
    private $formattedValue;

    /**
     * @var string
     */
    private $renderedValue;

    /**
     * @var int
     */
    private $rowspan;

    /**
     * @var int
     */
    private $colspan;

    /**
     * @var array
     */
    private $additionalStyleIndexes;

    /**
     * @var string
     */
    private $dataType;

    /**
     * @var int
     */
    private $styleIndex;

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
     * @var array
     */
    private $metaData;

    /**
     * CellDataValueObject constructor.
     *
     * @param Cell $cell
     *
     * @param string $renderedValue
     * @param int $rowspan
     * @param int $colspan
     * @param array $additionalStyles
     * @param array $metaData
     * @throws SpreadsheetException
     */
    public function __construct(
        Cell $cell,
        string $renderedValue,
        int $rowspan = 0,
        int $colspan = 0,
        array $additionalStyles = [],
        array $metaData = []
    ) {
        $this->calculatedValue = $cell->getCalculatedValue();
        $this->formattedValue = $cell->getFormattedValue();
        $this->renderedValue = $renderedValue;
        $this->rowspan = $rowspan;
        $this->colspan = $colspan;

        $this->dataType = $cell->getDataType();
        $this->styleIndex = $cell->getXfIndex();
        $this->additionalStyleIndexes = $additionalStyles;
        $this->metaData = $metaData;

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
     * @param string $renderedValue
     * @param int $rowspan
     * @param int $colspan
     * @param array $additionalStyles
     * @param array $metaData
     * @return CellDataValueObject
     * @throws SpreadsheetException
     */
    public static function create(
        Cell $cell,
        string $renderedValue,
        int $rowspan = 0,
        int $colspan = 0,
        array $additionalStyles = [],
        array $metaData = []
    ): CellDataValueObject {
        return new self($cell, $renderedValue, $rowspan, $colspan, $additionalStyles, $metaData);
    }

    /**
     * @return string
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * @return string|int|float|mixed
     */
    public function getCalculatedValue()
    {
        return $this->calculatedValue;
    }

    /**
     * @return string|int|float|mixed
     */
    public function getFormattedValue()
    {
        return $this->formattedValue;
    }

    /**
     * @return string
     */
    public function getRenderedValue(): string
    {
        return $this->renderedValue;
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
        $cellClass .= sprintf(' cell-type-%s', $this->dataType);
        $cellClass .= sprintf(' cell-style-%d', $this->styleIndex);

        foreach ($this->getAdditionalStyleIndexes() as $indexes) {
            $cellClass .= sprintf(' cell-style-%d', $indexes);
        }
        return $cellClass;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $data = ['val' => $this->getFormattedValue()];
        if ($this->getRowspan() > 1) {
            $data['row'] = $this->getRowspan();
        }
        if ($this->getColspan() > 1) {
            $data['col'] = $this->getColspan();
        }

        $css = $this->metaData['backendCellClasses'] ?? null;
        if (empty($css) === false) {
            $data['css'] = implode('-', $css);
        }

        return $data;
    }
}
