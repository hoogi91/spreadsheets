<?php

namespace Hoogi91\Spreadsheets\ViewHelpers\Cell;

use Closure;
use Hoogi91\Spreadsheets\Domain\ValueObject\CellDataValueObject;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Class RenderViewHelper
 * @package Hoogi91\Spreadsheets\ViewHelpers\Cell
 */
class RenderViewHelper extends AbstractViewHelper
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
            'Cell object for which table cell should be rendered',
            true
        );
        $this->registerArgument('isHeader', 'bool', 'True to render <th> otherwise it will be <td>', false, false);
        $this->registerArgument('scope', 'string', 'scope attribute value', false);
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
        $cell = $arguments['cell'] ?? null;
        if ($cell instanceof CellDataValueObject) {
            $attributes = $cell->getClass() !== '' ? ' class="' . $cell->getClass() . '"' : '';
            $attributes .= $cell->getRowspan() > 0 ? ' rowspan="' . $cell->getRowspan() . '"' : '';
            $attributes .= $cell->getColspan() > 0 ? ' colspan="' . $cell->getColspan() . '"' : '';
        }

        $isHeader = (bool)($arguments['isHeader'] ?? 0);
        if ($isHeader === true && isset($arguments['scope'])) {
            $attributes = ' scope="' . $arguments['scope'] . '"' . ($attributes ?? '');
        }

        return sprintf(
            $isHeader === true ? '<th%s>%s</th>' : '<td%s>%s</td>',
            $attributes ?? '',
            $renderChildrenClosure()
        );
    }
}
