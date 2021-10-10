<?php

namespace Hoogi91\Spreadsheets\ViewHelpers\Reader;

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class FileReferenceViewHelper
 * @package Hoogi91\Spreadsheets\ViewHelpers\Reader
 */
class FileReferenceViewHelper extends AbstractViewHelper
{

    /**
     * @var FileRepository
     */
    private $fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * Initialize arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'string', 'File reference uid to resolve', false);
    }

    /**
     * @return FileReference|null
     */
    public function render(): ?FileReference
    {
        if (empty($this->arguments['uid'])) {
            $this->arguments['uid'] = $this->renderChildren();
        }

        return !empty($this->arguments['uid']) && is_int($this->arguments['uid']) === true
            ? $this->fileRepository->findFileReferenceByUid($this->arguments['uid'])
            : null;
    }
}
