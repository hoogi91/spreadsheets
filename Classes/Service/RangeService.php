<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RangeService
{
    // match against => 2:24
    private const MATCH_PATTERN_1 = '/^(\d+):(\d+)$/';

    // match against => 2 (single integer/row value)
    private const MATCH_PATTERN_2 = '/^(\d+)$/';

    // match against => B:D
    private const MATCH_PATTERN_3 = '/^([a-zA-Z]+):([a-zA-Z]+)$/';

    // match against => B (single string/column value)
    private const MATCH_PATTERN_4 = '/^([a-zA-Z]+)$/';

    // match against => 2:B
    private const MATCH_PATTERN_5 = '/^(\d+):([a-zA-Z]+)$/';

    // match against => B:24
    private const MATCH_PATTERN_6 = '/^([a-zA-Z]+):(\d+)$/';

    // match against => B2:24
    private const MATCH_PATTERN_7 = '/^([a-zA-Z]+)(\d+):(\d+)$/';

    // match against => B2:D
    private const MATCH_PATTERN_8 = '/^([a-zA-Z]+)(\d+):([a-zA-Z]+)$/';

    // match against => 2:D24
    private const MATCH_PATTERN_9 = '/^(\d+):([a-zA-Z]+)(\d+)$/';

    // match against => B:D24
    private const MATCH_PATTERN_10 = '/^([a-zA-Z]+):([a-zA-Z]+)(\d+)$/';

    /**
     * @throws SpreadsheetException
     */
    public function convert(Worksheet $sheet, string $range): string
    {
        if (
            $sheet->getHighestColumn() === 'A'
            && $sheet->getHighestRow() === 1
            && $sheet->cellExists('A1') === false
        ) {
            return '';
        }

        if (preg_match(self::MATCH_PATTERN_1, $range, $matches) === 1) {
            // return range => A2:D24 (if highest Column is D)
            $range = $this->buildRange('A', (int)$matches[1], $sheet->getHighestColumn(), (int)$matches[2]);
        } elseif (preg_match(self::MATCH_PATTERN_2, $range, $matches) === 1) {
            // return range => A2:D2 (if highest Column is D)
            $range = $this->buildRange('A', (int)$matches[1], $sheet->getHighestColumn(), (int)$matches[1]);
        } elseif (preg_match(self::MATCH_PATTERN_3, $range, $matches) === 1) {
            // return range => B1:D24 (if highest row is 24)
            $range = $this->buildRange($matches[1], 1, $matches[2], (int)$sheet->getHighestRow());
        } elseif (preg_match(self::MATCH_PATTERN_4, $range, $matches) === 1) {
            // return range => B1:B24 (if highest row is 24)
            $range = $this->buildRange($matches[1], 1, $matches[1], (int)$sheet->getHighestRow());
        } elseif (preg_match(self::MATCH_PATTERN_5, $range, $matches) === 1) {
            // return single cell => B2
            $range = $this->buildRange($matches[2], (int)$matches[1]);
        } elseif (preg_match(self::MATCH_PATTERN_6, $range, $matches) === 1) {
            // return single cell => B24
            $range = $this->buildRange($matches[1], (int)$matches[2]);
        } elseif (preg_match(self::MATCH_PATTERN_7, $range, $matches) === 1) {
            // return range => B2:B24 (cause first part sets column)
            $range = $this->buildRange($matches[1], (int)$matches[2], $matches[1], (int)$matches[3]);
        } elseif (preg_match(self::MATCH_PATTERN_8, $range, $matches) === 1) {
            // return range => B2:D2 (cause first part sets row)
            $range = $this->buildRange($matches[1], (int)$matches[2], $matches[3], (int)$matches[2]);
        } elseif (preg_match(self::MATCH_PATTERN_9, $range, $matches) === 1) {
            // return range => D2:D24 (cause second part sets column)
            $range = $this->buildRange($matches[2], (int)$matches[1], $matches[2], (int)$matches[3]);
        } elseif (preg_match(self::MATCH_PATTERN_10, $range, $matches) === 1) {
            // return range => B24:D24 (cause second part sets row)
            $range = $this->buildRange($matches[1], (int)$matches[3], $matches[2], (int)$matches[3]);
        }

        return $sheet->shrinkRangeToFit($range);
    }

    private function buildRange(
        string $startColumn,
        int $startRow,
        string $endColumn = null,
        int $endRow = null
    ): string {
        return sprintf('%s%d:%s%d', $startColumn, $startRow, $endColumn ?? $startColumn, $endRow ?? $startRow);
    }
}
