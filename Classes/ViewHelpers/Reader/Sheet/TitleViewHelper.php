<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\ViewHelpers\Reader\Sheet;

use Hoogi91\Spreadsheets\Service\ReaderService;
use PhpOffice\PhpSpreadsheet\Exception;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class TitleViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly ReaderService $readerService)
    {
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('file', FileReference::class, 'The spreadsheet file reference', false);
        $this->registerArgument('index', 'integer', 'Index of worksheet that should be selected', false, 0);
    }

    public function render(): string
    {
        if (empty($this->arguments['file'])) {
            $this->arguments['file'] = $this->renderChildren();
        }
        if (
            $this->arguments['file'] instanceof FileReference === false
            || is_numeric($this->arguments['index']) === false
        ) {
            return '';
        }

        try {
            return $this->readerService->getSpreadsheet($this->arguments['file'])
                ->getSheet((int) $this->arguments['index'])
                ->getTitle();
        } catch (Exception) {
            return '';
        }
    }
}
