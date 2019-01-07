<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\ViewHelpers\Reader\Sheet;

use PhpOffice\PhpSpreadsheet\Exception;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Hoogi91\Spreadsheets\Service\ReaderService;

/**
 * Class TitleViewHelper
 * @package Hoogi91\Spreadsheets\ViewHelpers\Reader\Sheet
 */
class TitleViewHelper extends AbstractViewHelper implements CompilableInterface
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('file', FileReference::class, 'The spreadsheet file reference', false);
        $this->registerArgument('index', 'integer', 'Index of worksheet that should be selected', false, 0);
    }

    /**
     * @param array                     $arguments
     * @param \Closure                  $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        if (empty($arguments['file'])) {
            $arguments['file'] = $renderChildrenClosure();
        }

        if (!$arguments['file'] instanceof FileReference) {
            return '';
        }

        try {
            /** @var ReaderService $reader */
            $reader = GeneralUtility::makeInstance(ReaderService::class, $arguments['file']);
            $sheet = $reader->getSheet($arguments['index']);
            return $sheet->getTitle();
        } catch (Exception $e) {
            return '';
        }
    }
}
