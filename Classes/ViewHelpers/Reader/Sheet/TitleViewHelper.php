<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\ViewHelpers\Reader\Sheet;

use Closure;
use Hoogi91\Spreadsheets\Service\ReaderService;
use PhpOffice\PhpSpreadsheet\Exception;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Class TitleViewHelper
 * @package Hoogi91\Spreadsheets\ViewHelpers\Reader\Sheet
 */
class TitleViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var
     */
    private static $readerService;

    public function __construct(ReaderService $readerService)
    {
        self::$readerService = $readerService;
    }

    /**
     * Initialize arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('file', FileReference::class, 'The spreadsheet file reference', false);
        $this->registerArgument('index', 'integer', 'Index of worksheet that should be selected', false, 0);
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
        if (empty($arguments['file'])) {
            $arguments['file'] = $renderChildrenClosure();
        }
        if ($arguments['file'] instanceof FileReference === false) {
            return '';
        }

        try {
            return self::$readerService->getSpreadsheet($arguments['file'])
                ->getSheet((int)$arguments['index'])
                ->getTitle();
        } catch (Exception $e) {
            return '';
        }
    }
}
