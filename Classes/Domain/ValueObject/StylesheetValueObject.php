<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\ValueObject;

/**
 * Class StylesheetValueObject
 * @package Hoogi91\Spreadsheets\Domain\ValueObject
 */
class StylesheetValueObject
{

    /**
     * @var array
     */
    private $styles;

    /**
     * StylesheetValueObject constructor.
     * @param array $styles
     */
    public function __construct(array $styles)
    {
        $this->styles = $styles;
    }

    /**
     * @param array $styles
     * @return StylesheetValueObject
     */
    public static function create(array $styles): StylesheetValueObject
    {
        return new self($styles);
    }

    /**
     * Takes array where of CSS properties / values and converts to CSS string.
     *
     * @param array $styles
     *
     * @return string
     */
    private function assembleStyles($styles = []): string
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

    public function toInlineCSS(): string
    {
        return $this->assembleStyles($this->styles);
    }

    /**
     * @param string|null $htmlIdentifier
     * @return string
     */
    public function toCSS(?string $htmlIdentifier = null): string
    {
        if (empty($this->styles)) {
            return '';
        }

        // write all styles with table selector prefix
        $content = '';
        foreach ($this->styles as $styleName => $styleDefinition) {
            if ($styleName !== 'html') {
                if (empty($htmlIdentifier) === false) {
                    $content .= vsprintf('#%s %s {%s}' . PHP_EOL, [
                        $htmlIdentifier,
                        $styleName,
                        $this->assembleStyles($styleDefinition),
                    ]);
                } else {
                    $content .= vsprintf('%s {%s}' . PHP_EOL, [
                        $styleName,
                        $this->assembleStyles($styleDefinition),
                    ]);
                }
            }
        }
        return $content;
    }

    public function __toString(): string
    {
        return $this->toCSS();
    }
}