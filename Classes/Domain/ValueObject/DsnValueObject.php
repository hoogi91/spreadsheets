<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\ValueObject;

use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class DsnValueObject
 * @package Hoogi91\Spreadsheets\Domain\ValueObject
 */
class DsnValueObject
{

    /**
     * @var FileReference
     */
    private $fileReference;

    /**
     * @var int
     */
    private $sheetIndex;

    /**
     * @var string|null
     */
    private $selection;

    /**
     * @var string|null
     */
    private $directionOfSelection;

    /**
     * TODO: move to DSN-like format to parse spreadsheet value with more elegance
     * e.g. spreadsheet://123?index=1&range=A2:B5&direction=vertical
     *
     * @param string $dsn
     *
     * @throws InvalidDataSourceNameException
     */
    public function __construct(string $dsn)
    {
        [$file, $fullSelection] = GeneralUtility::trimExplode('|', $dsn, false, 2);
        if (empty($file)) {
            throw new InvalidDataSourceNameException('File reference is required in spreadsheet DSN!');
        }

        try {
            if (strpos($file, 'file:') === 0 && (int)substr($file, 5) !== 0) {
                /** @var FileRepository $fileRepository */
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $this->fileReference = $fileRepository->findFileReferenceByUid((int)substr($file, 5));
            } elseif ((int)$file !== 0 && MathUtility::canBeInterpretedAsInteger($file)) {
                /** @var FileRepository $fileRepository */
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $this->fileReference = $fileRepository->findFileReferenceByUid((int)$file);
            } else {
                throw new InvalidDataSourceNameException('File reference from DSN can not be parsed/evaluated!');
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ResourceDoesNotExistException $exception) {
            throw new InvalidDataSourceNameException('Referenced file resource could not be found!');
        }

        if (isset($fullSelection) && ((string)$fullSelection) !== '') {
            [$sheetIndex, $selection, $directionOfSelection] = GeneralUtility::trimExplode(
                '!',
                $fullSelection,
                false,
                3
            );

            $this->sheetIndex = (int)($sheetIndex ?? 0);
            $this->selection = $selection ?: null;
            $this->directionOfSelection = $directionOfSelection ?: null;
        }

        if ($this->sheetIndex < 0) {
            throw new InvalidDataSourceNameException('Spreadsheet DSN has an invalid sheet index provided!');
        }
    }

    /**
     * @param string $dsn
     * @return DsnValueObject
     *
     * @throws InvalidDataSourceNameException
     */
    public static function createFromDSN(string $dsn): DsnValueObject
    {
        return new self($dsn);
    }

    /**
     * @return string
     */
    public function getDsn(): string
    {
        $dsn = sprintf('file:%d|%d', $this->getFileReference()->getUid(), $this->getSheetIndex());
        if ($this->getSelection() !== null) {
            $dsn .= '!' . $this->getSelection();

            if ($this->getDirectionOfSelection() !== null) {
                $dsn .= '!' . $this->getDirectionOfSelection();
            }
        }
        return $dsn;
    }

    /**
     * @return FileReference
     */
    public function getFileReference(): FileReference
    {
        return $this->fileReference;
    }

    /**
     * @return int
     */
    public function getSheetIndex(): int
    {
        return $this->sheetIndex;
    }

    /**
     * @return string|null
     */
    public function getSelection(): ?string
    {
        return $this->selection;
    }

    /**
     * @return string|null
     */
    public function getDirectionOfSelection(): ?string
    {
        return $this->directionOfSelection;
    }
}
