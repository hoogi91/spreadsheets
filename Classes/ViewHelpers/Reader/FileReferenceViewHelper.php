<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\ViewHelpers\Reader;

use InvalidArgumentException;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class FileReferenceViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly FileRepository $fileRepository)
    {
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'string', 'File reference uid to resolve', false);
    }

    public function render(): ?FileReference
    {
        if (empty($this->arguments['uid'])) {
            $this->arguments['uid'] = $this->renderChildren();
        }

        try {
            $fileReference = $this->fileRepository->findFileReferenceByUid($this->arguments['uid']);
        } catch (InvalidArgumentException) {
            $fileReference = false;
        }

        return !is_bool($fileReference) ? $fileReference : null;
    }
}
