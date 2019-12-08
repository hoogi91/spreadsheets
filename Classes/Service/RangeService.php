<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class RangeService
 * @package Hoogi91\Spreadsheets\Service
 */
class RangeService
{

    /**
     * converts range string from database value for given worksheet
     *
     * @param Worksheet $sheet
     * @param string $range
     *
     * @return string
     */
    public function convert(Worksheet $sheet, string $range): string
    {
        // match against => 2:24
        if (preg_match('/^(\d+):(\d+)$/', $range, $matches) === 1) {
            // return range => A2:D24 (if highest Column is D)
            return $this->buildRange('A', (int)$matches[1], $sheet->getHighestColumn(), (int)$matches[2]);
        }
        // match against => 2 (single integer/row value)
        if (preg_match('/^(\d+)$/', $range, $matches) === 1) {
            // return range => A2:D2 (if highest Column is D)
            return $this->buildRange('A', (int)$matches[1], $sheet->getHighestColumn(), (int)$matches[1]);
        }
        // match against => B:D
        if (preg_match('/^([a-zA-Z]+):([a-zA-Z]+)$/', $range, $matches) === 1) {
            // return range => B1:D24 (if highest row is 24)
            return $this->buildRange($matches[1], 1, $matches[2], (int)$sheet->getHighestRow());
        }
        // match against => B (single string/column value)
        if (preg_match('/^([a-zA-Z]+)$/', $range, $matches) === 1) {
            // return range => B1:B24 (if highest row is 24)
            return $this->buildRange($matches[1], 1, $matches[1], (int)$sheet->getHighestRow());
        }
        // match against => 2:B
        if (preg_match('/^(\d+):([a-zA-Z]+)$/', $range, $matches) === 1) {
            // return single cell => B2
            return $this->buildRange($matches[2], (int)$matches[1]);
        }
        // match against => B:24
        if (preg_match('/^([a-zA-Z]+):(\d+)$/', $range, $matches) === 1) {
            // return single cell => B24
            return $this->buildRange($matches[1], (int)$matches[2]);
        }
        // match against => B2:24
        if (preg_match('/^([a-zA-Z]+)(\d+):(\d+)$/', $range, $matches) === 1) {
            // return range => B2:B24 (cause first part sets column)
            return $this->buildRange($matches[1], (int)$matches[2], $matches[1], (int)$matches[3]);
        }
        // match against => B2:D
        if (preg_match('/^([a-zA-Z]+)(\d+):([a-zA-Z]+)$/', $range, $matches) === 1) {
            // return range => B2:D2 (cause first part sets row)
            return $this->buildRange($matches[1], (int)$matches[2], $matches[3], (int)$matches[2]);
        }
        // match against => 2:D24
        if (preg_match('/^(\d+):([a-zA-Z]+)(\d+)$/', $range, $matches) === 1) {
            // return range => D2:D24 (cause second part sets column)
            return $this->buildRange($matches[2], (int)$matches[1], $matches[2], (int)$matches[3]);
        }
        // match against => B:D24
        if (preg_match('/^([a-zA-Z]+):([a-zA-Z]+)(\d+)$/', $range, $matches) === 1) {
            // return range => B24:D24 (cause second part sets row)
            return $this->buildRange($matches[1], (int)$matches[3], $matches[2], (int)$matches[3]);
        }
        return $range;
    }

    /**
     * @param string $startColumn
     * @param int $startRow
     * @param string $endColumn
     * @param int $endRow
     *
     * @return string
     */
    private function buildRange(string $startColumn, int $startRow, string $endColumn = null, int $endRow = null): string
    {
        if ($endColumn === null && $endRow === null) {
            return sprintf('%s%d', $startColumn, $startRow);
        }
        return sprintf('%s%d:%s%d', $startColumn, $startRow, $endColumn, $endRow);
    }
}
