<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\ViewHelpers\Value;

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Hoogi91\Spreadsheets\Domain\Model\SpreadsheetValue;

/**
 * Class GetViewHelper
 * @package Hoogi91\Spreadsheets\ViewHelpers\Value
 */
class GetViewHelper extends AbstractViewHelper implements CompilableInterface
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('subject', 'string', 'database value to parse to spreadsheet value', false);
    }

    /**
     * @param array                     $arguments
     * @param \Closure                  $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return SpreadsheetValue
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        if (empty($arguments['subject'])) {
            $arguments['subject'] = $renderChildrenClosure();
        }

        if (empty($arguments['subject']) || !is_string($arguments['subject'])) {
            return null;
        }

        $value = SpreadsheetValue::createFromDatabaseString($arguments['subject']);
        if (!$value->getFileReference() instanceof FileReference) {
            return null;
        }
        return $value;
    }
}
