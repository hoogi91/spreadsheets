<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\ViewHelpers\Value;

use Closure;
use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class GetViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('subject', 'string', 'database value to parse to spreadsheet value', false);
    }

    /**
     * @param array<mixed> $arguments
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): ?DsnValueObject {
        if (empty($arguments['subject'])) {
            $arguments['subject'] = $renderChildrenClosure();
        }
        if (empty($arguments['subject']) || is_string($arguments['subject']) === false) {
            return null;
        }

        return DsnValueObject::createFromDSN($arguments['subject']);
    }
}
