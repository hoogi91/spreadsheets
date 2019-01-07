<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\Model;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

/**
 * Class CellValue
 * @package Hoogi91\Spreadsheets\Domain\Model
 */
class CellValue
{

    /**
     * @var Cell
     */
    protected $cell;

    /**
     * @var bool
     */
    protected $isRichText = false;

    /**
     * @var mixed|string
     */
    protected $value = '';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var int
     */
    protected $styleIndex = 0;

    /**
     * @var array
     */
    protected $additionalStyleIndexes = [];

    /**
     * @var bool
     */
    protected $superscript = false;

    /**
     * @var bool
     */
    protected $subscript = false;

    /**
     * @var string
     */
    protected $hyperlink = '';

    /**
     * @var string
     */
    protected $hyperlinkTitle = '';

    /**
     * @var int
     */
    protected $rowspan = 0;

    /**
     * @var int
     */
    protected $colspan = 0;

    /**
     * CellValue constructor.
     *
     * @param Cell $cell
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function __construct(Cell $cell)
    {
        $this->cell = $cell;
        $this->setValue($cell->getValue());

        $this->type = $cell->getDataType();

        // create cell styling
        $this->styleIndex = $cell->getXfIndex();

        // check for super- and subscript
        $cellValue = $cell->getValue();
        if ($cellValue instanceof RichText) {
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
     * @return Cell
     */
    public function getCell(): Cell
    {
        return $this->cell;
    }

    /**
     * @return bool
     */
    public function getIsRichText(): bool
    {
        return $this->isRichText;
    }

    /**
     * @return string|int|float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string|int|float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
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
     * @param array $additionalStyleIndexes
     */
    public function setAdditionalStyleIndexes(array $additionalStyleIndexes)
    {
        $this->additionalStyleIndexes = $additionalStyleIndexes;
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
     * @param string $hyperlink
     */
    public function setHyperlink(string $hyperlink)
    {
        $this->hyperlink = $hyperlink;
    }

    /**
     * @return string
     */
    public function getHyperlinkTitle(): string
    {
        return $this->hyperlinkTitle;
    }

    /**
     * @param string $hyperlinkTitle
     */
    public function setHyperlinkTitle(string $hyperlinkTitle)
    {
        $this->hyperlinkTitle = $hyperlinkTitle;
    }

    /**
     * @return int
     */
    public function getRowspan(): int
    {
        return $this->rowspan;
    }

    /**
     * @param int $rowspan
     */
    public function setRowspan(int $rowspan)
    {
        $this->rowspan = $rowspan;
    }

    /**
     * @return int
     */
    public function getColspan(): int
    {
        return $this->colspan;
    }

    /**
     * @param int $colspan
     */
    public function setColspan(int $colspan)
    {
        $this->colspan = $colspan;
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
