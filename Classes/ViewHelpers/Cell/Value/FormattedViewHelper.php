<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\ViewHelpers\Cell\Value;

use Closure;
use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Class FormattedViewHelper
 * @package Hoogi91\Spreadsheets\ViewHelpers\Cell\Value
 */
class FormattedViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'cell',
            CellDataValueObject::class,
            'Cell data to format with all tags and hyperlink if available',
            false
        );
        $this->registerArgument(
            'target',
            'string',
            'Link target if cell contains a hyperlink',
            false,
            '_blank'
        );
    }

    /**
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        /** @var CellDataValueObject|null $cell */
        $cell = $arguments['cell'];
        if ($cell === null) {
            $cell = $renderChildrenClosure();
        }
        if (empty($cell) || $cell instanceof CellDataValueObject === false) {
            return '';
        }

        // get formatted value to simple html
        $value = $cell->getFormattedValue();
        if ($cell->isRichText() !== true) {
            // convert html special chars if we do not have rich text content
            $value = htmlspecialchars($value, ENT_QUOTES);
        }

        // now add html breaks for newlines
        $value = nl2br($value);

        // extend value with superscript or subscript tags
        if ($cell->isSuperscript() === true) {
            $value = '<sup>' . $value . '</sup>';
        } elseif ($cell->isSubscript() === true) {
            $value = '<sub>' . $value . '</sub>';
        }

        if (empty($cell->getHyperlink())) {
            // return formatted value if we do not need hyperlink
            return $value;
        }

        // at least wrap content as link if required
        return sprintf(
            '<a href="%1$s" target="%3$s" title="%2$s">%4$s</a>',
            $cell->getHyperlink(),
            $cell->getHyperlinkTitle(),
            $arguments['target'] ?? '_blank',
            $value
        );
    }
}
