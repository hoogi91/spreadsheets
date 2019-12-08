<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\ViewHelpers\Value;

use Closure;
use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Class GetViewHelper
 * @package Hoogi91\Spreadsheets\ViewHelpers\Value
 */
class GetViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('subject', 'string', 'database value to parse to spreadsheet value', false);
    }

    /**
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return DsnValueObject
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): DsnValueObject
    {
        if (empty($arguments['subject'])) {
            $arguments['subject'] = $renderChildrenClosure();
        }
        if (empty($arguments['subject']) || is_string($arguments['subject']) === false) {
            return null;
        }

        $value = DsnValueObject::createFromDSN($arguments['subject']);
        if ($value->getFileReference() instanceof FileReference === false) {
            return null;
        }
        return $value;
    }
}
