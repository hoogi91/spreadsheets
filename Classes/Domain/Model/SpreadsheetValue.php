<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\Model;

use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
            list($sheetIndex, $selection) = GeneralUtility::trimExplode('!', $fullSelection, false, 2);
            $object->sheetIndex = (int)$sheetIndex;
            $object->selection = $selection ?: '';

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
    public function getFormattedValue(): string
    {
        if (empty($this->getSheetName())) {
            return $this->getSelection();
        } elseif (empty($this->getSelection())) {
            return $this->getSheetName();
        }
        return $this->getSheetName() . '!' . $this->getSelection();
    }

    /**
     * @return string
     */
    public function getDatabaseValue(): string
    {
        return 'file:' . $this->getFileReferenceUid() . '|' . $this->getSheetIndex() . '!' . $this->getSelection();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getFormattedValue();
    }
}
