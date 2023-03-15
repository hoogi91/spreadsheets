<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Domain\ValueObject;

use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use JsonSerializable;
use Stringable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class DsnValueObject implements JsonSerializable, Stringable
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

    private int $fileReference = 0;

    private int $sheetIndex = 0;

    private ?string $selection = null;

    private ?string $directionOfSelection = null;

    /**
     * @throws InvalidDataSourceNameException
     */
    public function __construct(string $dsn)
    {
        if (preg_match(self::LEGACY_DSN_PATTERN, $dsn) === 1) {
            $this->legacyDSNParsing($dsn);
        } elseif (preg_match(self::DSN_PATTERN, $dsn) === 1) {
            $dsnData = parse_url($dsn);
            if (
                !isset($dsnData['host'])
                || !MathUtility::canBeInterpretedAsInteger($dsnData['host'])
                || (int)$dsnData['host'] <= 0
            ) {
                throw new InvalidDataSourceNameException('File reference from DSN is not valid!');
            }

            parse_str($dsnData['query'] ?? '', $queryData);
            $this->fileReference = (int)$dsnData['host'];
            $this->sheetIndex = MathUtility::canBeInterpretedAsInteger($queryData['index'] ?? null)
                ? (int)$queryData['index']
                : 0;
            $this->selection = is_string($queryData['range'] ?? null) ? $queryData['range'] : null;
            $this->directionOfSelection = is_string($queryData['direction'] ?? null) ? $queryData['direction'] : null;
        } else {
            throw new InvalidDataSourceNameException('Spreadsheet DSN could not be parsed!');
        }

        if ($this->sheetIndex < 0) {
            throw new InvalidDataSourceNameException('Spreadsheet DSN has an invalid sheet index provided!');
        }
    }

    private function legacyDSNParsing(string $dsn): void
    {
        [$file, $fullSelection] = GeneralUtility::trimExplode('|', $dsn, false, 2);
        if (str_starts_with($file, 'file:') && (int)substr($file, 5) !== 0) {
            $this->fileReference = (int)substr($file, 5);
        } elseif ((int)$file !== 0 && MathUtility::canBeInterpretedAsInteger($file)) {
            $this->fileReference = (int)$file;
        } else {
            throw new InvalidDataSourceNameException('File reference from DSN can not be parsed/evaluated!');
        }

        if (trim($fullSelection) === '') {
            return;
        }

        [$sheetIndex, $selection, $directionOfSelection] = array_pad(
            GeneralUtility::trimExplode('!', $fullSelection, false, 3),
            3,
            ''
        );

        $this->sheetIndex = (int)($sheetIndex ?: 0);
        $this->selection = $selection ?: null;
        $this->directionOfSelection = $directionOfSelection ?: null;
    }

    /**
     * @throws InvalidDataSourceNameException
     */
    public static function createFromDSN(string $dsn): DsnValueObject
    {
        return new self($dsn);
    }

    public function getDsn(): string
    {
        $parameters = [
            'index' => $this->getSheetIndex(),
            'range' => $this->getSelection(),
            'direction' => $this->getDirectionOfSelection(),
        ];

        $parameters = array_filter(
            $parameters,
            static fn ($value) => $value !== null
        );

        return sprintf('spreadsheet://%d?%s', $this->getFileReference(), http_build_query($parameters));
    }

    public function getFileReference(): int
    {
        return $this->fileReference;
    }

    public function getSheetIndex(): int
    {
        return $this->sheetIndex;
    }

    public function getSelection(): ?string
    {
        return $this->selection;
    }

    public function getDirectionOfSelection(): ?string
    {
        return $this->directionOfSelection;
    }

    public function __toString(): string
    {
        return $this->getDsn();
    }

    public function jsonSerialize(): string
    {
        return $this->getDsn();
    }
}
