<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\ValueObject;

use JsonSerializable;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class CellDataValueObject implements JsonSerializable
{
    private mixed $calculatedValue;

    private readonly string $formattedValue;

    private readonly string $dataType;

    private readonly int $styleIndex;

    private bool $isRichText = false;

    private bool $superscript = false;

    private bool $subscript = false;

    private string $hyperlink = '';

    private string $hyperlinkTitle = '';

    /**
     * @param array<int> $additionalStyleIndexes
     * @param array<string, array<string>> $metaData
     * @throws SpreadsheetException
     */
    public function __construct(
        Cell $cell,
        private readonly string $renderedValue,
        private readonly int $rowspan = 0,
        private readonly int $colspan = 0,
        private readonly array $additionalStyleIndexes = [],
        private readonly array $metaData = []
    ) {
        $this->calculatedValue = $cell->getCalculatedValue();
        $this->formattedValue = $cell->getFormattedValue();

        $this->dataType = $cell->getDataType();
        $this->styleIndex = $cell->getXfIndex();

        // check for super- and subscript
        if ($cell->getValue() instanceof RichText) {
            // set rich text option true to ignore styling - cause value has integrated styling
            $this->isRichText = true;
        } else {
            $this->superscript = $cell->getStyle()->getFont()->getSuperscript() ?? false;
            $this->subscript = $cell->getStyle()->getFont()->getSubscript() ?? false;
        }

        // Hyperlink?
        if ($cell->hasHyperlink() !== true || $cell->getHyperlink()->isInternal() !== false) {
            return;
        }

        $this->hyperlink = $cell->getHyperlink()->getUrl();
        $this->hyperlinkTitle = $cell->getHyperlink()->getTooltip();
    }

    /**
     * @param array<int> $additionalStyles
     * @param array<string, array<string>> $metaData
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

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function getCalculatedValue(): mixed
    {
        return $this->calculatedValue;
    }

    public function getFormattedValue(): mixed
    {
        return $this->formattedValue;
    }

    public function getRenderedValue(): string
    {
        return $this->renderedValue;
    }

    public function getStyleIndex(): int
    {
        return $this->styleIndex;
    }

    /**
     * @return array<int>
     */
    public function getAdditionalStyleIndexes(): array
    {
        return $this->additionalStyleIndexes;
    }

    public function isRichText(): bool
    {
        return $this->isRichText;
    }

    public function isSuperscript(): bool
    {
        return $this->superscript;
    }

    public function isSubscript(): bool
    {
        return $this->subscript;
    }

    public function getHyperlink(): string
    {
        return $this->hyperlink;
    }

    public function getHyperlinkTitle(): string
    {
        return $this->hyperlinkTitle;
    }

    public function getRowspan(): int
    {
        return $this->rowspan;
    }

    public function getColspan(): int
    {
        return $this->colspan;
    }

    public function getClass(): string
    {
        $cellClass = 'cell';
        $cellClass .= sprintf(' cell-type-%s', $this->dataType);
        $cellClass .= sprintf(' cell-style-%d', $this->styleIndex);

        foreach ($this->getAdditionalStyleIndexes() as $index) {
            $cellClass .= sprintf(' cell-style-%d', $index);
        }

        return $cellClass;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
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
