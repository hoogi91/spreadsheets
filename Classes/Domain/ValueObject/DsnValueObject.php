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
     * Legacy DSN pattern matches all strings like:
     *   - file:10|
     *   - file:10|2
     *   - file:10|2!
     *   - file:10|2!AA2
     *   - file:10|2!A22:B5
     *   - file:10|2!A2:B555!vertical2
     *   - 5|1!D2:G5!vertical
     */
    private const LEGACY_DSN_PATTERN = '/(file:)?(\d+)\|(\d+)(![A-Z]+\d+)?(:[A-Z]+\d+)?(!\w+)?/';

    /**
     * DSN pattern will match strings like
     *   - spreadsheet://123
     *   - spreadsheet://123?param=1
     *   - spreadsheet://123?param=1&param=2
     */
    private const DSN_PATTERN = '/^spreadsheet:\/\/(\d+)(\?.*)?/';

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
     * @param string $dsn
     *
     * @throws InvalidDataSourceNameException
     */
    public function __construct(string $dsn)
    {
        try {
            if (preg_match(self::LEGACY_DSN_PATTERN, $dsn) === 1) {
                $this->legacyDSNParsing($dsn);
            } elseif (preg_match(self::DSN_PATTERN, $dsn) === 1) {
                $dsnData = parse_url($dsn);
                parse_str($dsnData['query'], $queryData);
                if (MathUtility::canBeInterpretedAsInteger($dsnData['host'])) {
                    /** @var FileRepository $fileRepository */
                    $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                    $this->fileReference = $fileRepository->findFileReferenceByUid((int)$dsnData['host']);
                } else {
                    throw new InvalidDataSourceNameException('File reference from DSN can not be parsed/evaluated!');
                }

                $this->sheetIndex = (int)($queryData['index'] ?? 0);
                $this->selection = $queryData['range'] ?: null;
                $this->directionOfSelection = $queryData['direction'] ?: null;
            } else {
                throw new InvalidDataSourceNameException('Spreadsheet DSN could not be parsed!');
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ResourceDoesNotExistException $exception) {
            throw new InvalidDataSourceNameException(
                'Referenced file resource could not be found!',
                1578420075,
                $exception
            );
        }

        if ($this->sheetIndex < 0) {
            throw new InvalidDataSourceNameException('Spreadsheet DSN has an invalid sheet index provided!');
        }
    }

    /**
     * @param string $dsn
     */
    private function legacyDSNParsing(string $dsn): void
    {
        [$file, $fullSelection] = GeneralUtility::trimExplode('|', $dsn, false, 2);
        if (empty($file)) {
            throw new InvalidDataSourceNameException('File reference is required in spreadsheet DSN!');
        }

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

        if (empty($fullSelection) === false) {
            [$sheetIndex, $selection, $directionOfSelection] = GeneralUtility::trimExplode(
                '!',
                $fullSelection,
                false,
                3
            );

            $this->sheetIndex = (int)($sheetIndex ?: 0);
            $this->selection = $selection ?: null;
            $this->directionOfSelection = $directionOfSelection ?: null;
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
        $parameters = [
            'index' => $this->getSheetIndex(),
            'range' => $this->getSelection(),
            'direction' => $this->getDirectionOfSelection(),
        ];

        $parameters = array_filter(
            $parameters,
            static function ($value) {
                return $value !== null;
            }
        );

        return sprintf(
            'spreadsheet://%d?%s',
            $this->getFileReference()->getUid(),
            http_build_query($parameters)
        );
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

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDsn();
    }
}
