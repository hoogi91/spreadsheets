<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\ValueObject;

use Stringable;

use const PHP_EOL;

class StylesheetValueObject implements Stringable
{
    /**
     * @param array<string, mixed> $styles
     */
    public function __construct(private readonly array $styles)
    {
    }

    /**
     * @param array<string, mixed> $styles
     */
    public static function create(array $styles): StylesheetValueObject
    {
        return new self($styles);
    }

    private function assembleStyles(mixed $styles = []): string
    {
        if (is_array($styles) === false) {
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

    public function toCSS(?string $htmlIdentifier = null): string
    {
        // write all styles with table selector prefix
        $content = '';
        foreach ($this->styles as $styleName => $styleDefinition) {
            if ($styleName !== 'html') {
                if (empty($htmlIdentifier) === false) {
                    $content .= sprintf('#%s ', $htmlIdentifier);
                }
                $content .= vsprintf(
                    '%s {%s}' . PHP_EOL,
                    [
                        $styleName,
                        $this->assembleStyles($styleDefinition),
                    ]
                );
            }
        }

        return $content;
    }

    public function __toString(): string
    {
        return $this->toCSS();
    }
}
