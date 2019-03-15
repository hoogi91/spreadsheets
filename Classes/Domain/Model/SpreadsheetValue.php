<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\Model;

use Hoogi91\Spreadsheets\Service\ExtractorService;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class SpreadsheetValue
 * @package Hoogi91\Spreadsheets\Domain\Model
 */
class SpreadsheetValue
{
    /**
     * @var array
     */
    protected static $fileReferences = [];

    /**
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * @var int
     */
    protected $fileReferenceUid = 0;

    /**
     * @var int
     */
    protected $sheetIndex = 0;

    /**
     * @var string
     */
    protected $sheetName = '';

    /**
     * @var string
     */
    protected $selection = '';

    /**
     * @var string
     */
    protected $directionOfSelection = ExtractorService::EXTRACT_DIRECTION_HORIZONTAL;

    /**
     * SpreadsheetValue constructor.
     *
     * @param FileRepository $fileRepository
     */
    public function __construct(FileRepository $fileRepository = null)
    {
        $this->fileRepository = $fileRepository;
        if ($this->fileRepository === null) {
            $this->fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        }
    }

    /**
     * TODO: add DSN-like format in next major version 2.0 to parse out spreadsheet value with more elegance
     * e.g. spreadsheet://123?index=1&range=A2:B5&direction=vertical
     * IMPORTANT! add update script for database :)
     *
     * @param string $string
     * @param array  $sheetData
     *
     * @return SpreadsheetValue
     */
    public static function createFromDatabaseString(string $string, array $sheetData = []): SpreadsheetValue
    {
        /** @var SpreadsheetValue $object */
        $object = GeneralUtility::makeInstance(SpreadsheetValue::class);
        list($file, $fullSelection) = GeneralUtility::trimExplode('|', $string, false, 2);

        if (!empty($file) && strpos($file, 'file:') === 0) {
            $object->fileReferenceUid = (int)substr($file, 5);
        } elseif (!empty($file) && MathUtility::canBeInterpretedAsInteger($file)) {
            $object->fileReferenceUid = (int)$file;
        }
        if (isset($fullSelection) && strlen((string)$fullSelection) > 0) {
            list($sheetIndex, $selection, $directionOfSelection) = GeneralUtility::trimExplode(
                '!',
                $fullSelection,
                false,
                3
            );

            $object->sheetIndex = (int)$sheetIndex;
            $object->selection = $selection ?: '';
            $object->directionOfSelection = $directionOfSelection ?: ExtractorService::EXTRACT_DIRECTION_HORIZONTAL;

            if (isset($sheetData[$object->fileReferenceUid][$object->sheetIndex]['name'])) {
                $object->sheetName = $sheetData[$object->fileReferenceUid][$object->sheetIndex]['name'];
            }
        }
        return $object;
    }

    /**
     * @return int
     */
    public function getFileReferenceUid(): int
    {
        return (int)$this->fileReferenceUid;
    }

    /**
     * @param int $fileReferenceUid
     */
    public function setFileReferenceUid(int $fileReferenceUid)
    {
        $this->fileReferenceUid = $fileReferenceUid;
    }

    /**
     * @return FileReference
     */
    public function getFileReference()
    {
        if (empty($this->fileReferenceUid)) {
            return null;
        }

        if (isset(static::$fileReferences[$this->fileReferenceUid])) {
            return static::$fileReferences[$this->fileReferenceUid];
        }

        try {
            static::$fileReferences[$this->fileReferenceUid] = $this->fileRepository->findFileReferenceByUid(
                $this->fileReferenceUid
            );
        } catch (ResourceDoesNotExistException $e) {
            return null;
        }
        return static::$fileReferences[$this->fileReferenceUid] ?: null;
    }

    /**
     * @return int
     */
    public function getSheetIndex(): int
    {
        return (int)$this->sheetIndex;
    }

    /**
     * @param int $sheetIndex
     */
    public function setSheetIndex(int $sheetIndex)
    {
        $this->sheetIndex = $sheetIndex;
    }

    /**
     * @return string
     */
    public function getSheetName(): string
    {
        return $this->sheetName;
    }

    /**
     * @param string $sheetName
     */
    public function setSheetName(string $sheetName)
    {
        $this->sheetName = $sheetName;
    }

    /**
     * @return string
     */
    public function getSelection(): string
    {
        return $this->selection;
    }

    /**
     * @param string $selection
     */
    public function setSelection(string $selection)
    {
        $this->selection = $selection;
    }

    /**
     * @return string
     */
    public function getDirectionOfSelection(): string
    {
        return $this->directionOfSelection;
    }

    /**
     * @param string $directionOfSelection
     */
    public function setDirectionOfSelection(string $directionOfSelection)
    {
        $this->directionOfSelection = $directionOfSelection;
    }

    /**
     * @return string
     */
    public function getFormattedValue(): string
    {
        if (empty($this->getSheetName()) || empty($this->getSelection())) {
            return vsprintf('%s!%s', [
                $this->getSheetName() ?: $this->getSelection(),
                $this->getDirectionOfSelection(),
            ]);
        }
        return vsprintf('%s!%s!%s', [
            $this->getSheetName(),
            $this->getSelection(),
            $this->getDirectionOfSelection(),
        ]);
    }

    /**
     * @return string
     */
    public function getDatabaseValue(): string
    {
        return vsprintf('file:%d|%d!%s!%s', [
            $this->getFileReferenceUid(),
            $this->getSheetIndex(),
            $this->getSelection(),
            $this->getDirectionOfSelection(),
        ]);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getFormattedValue();
    }
}
