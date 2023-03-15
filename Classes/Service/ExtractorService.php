<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use Hoogi91\Spreadsheets\Domain\ValueObject;
use Iterator;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Column;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileRepository;

class ExtractorService
{
    final public const EXTRACT_DIRECTION_HORIZONTAL = 'horizontal';
    final public const EXTRACT_DIRECTION_VERTICAL = 'vertical';

    public function __construct(
        private readonly ReaderService $readerService,
        private readonly CellService $cellService,
        private readonly SpanService $spanService,
        private readonly RangeService $rangeService,
        private readonly ValueMappingService $mappingService,
        private readonly FileRepository $fileRepository
    ) {
    }

    /**
     * @throws SpreadsheetReaderException
     * @throws ResourceDoesNotExistException
     */
    public function getDataByDsnValueObject(
        ValueObject\DsnValueObject $dsnValue,
        bool $returnCellRef = false
    ): ValueObject\ExtractionValueObject {
        $spreadsheet = $this->readerService->getSpreadsheet(
            $this->fileRepository->findFileReferenceByUid($dsnValue->getFileReference())
        );

        try {
            // calculate correct range from worksheet or selection
            $worksheet = $spreadsheet->setActiveSheetIndex($dsnValue->getSheetIndex());
            $range = $dsnValue->getSelection();
            if ($range === null) {
                return ValueObject\ExtractionValueObject::create(
                    $spreadsheet,
                    $this->getBodyData($worksheet, $returnCellRef),
                    $this->getHeadData($worksheet, $returnCellRef)
                );
            }

            // get cell data and return value object
            $cellData = $this->rangeToCellArray(
                $worksheet,
                $range,
                $dsnValue->getDirectionOfSelection() ?? self::EXTRACT_DIRECTION_HORIZONTAL,
                $returnCellRef
            );

            return ValueObject\ExtractionValueObject::create($spreadsheet, $cellData);
        } catch (SpreadsheetException) {
            return ValueObject\ExtractionValueObject::create($spreadsheet, []);
        }
    }

    /**
     * @return array<array<ValueObject\CellDataValueObject>>
     */
    public function getHeadData(Worksheet\Worksheet $sheet, bool $returnCellRef = false): array
    {
        try {
            if ($sheet->getPageSetup()->isRowsToRepeatAtTopSet() === false) {
                return [];
            }

            $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();
            $range = 'A1:' . $sheet->getHighestColumn() . $rowsToRepeatAtTop[1];

            return $this->rangeToCellArray($sheet, $range, self::EXTRACT_DIRECTION_HORIZONTAL, $returnCellRef);
        } catch (SpreadsheetException) {
            // sheet or range of cells couldn't be loaded
            return [];
        }
    }

    /**
     * @return array<array<ValueObject\CellDataValueObject>>
     */
    public function getBodyData(Worksheet\Worksheet $sheet, bool $returnCellRef = false): array
    {
        try {
            if ($sheet->getPageSetup()->isRowsToRepeatAtTopSet() === false) {
                $range = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();

                return $this->rangeToCellArray($sheet, $range, self::EXTRACT_DIRECTION_HORIZONTAL, $returnCellRef);
            }

            $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();
            $range = 'A' . ($rowsToRepeatAtTop[1] + 1) . ':' . $sheet->getHighestColumn() . $sheet->getHighestRow();

            return $this->rangeToCellArray($sheet, $range, self::EXTRACT_DIRECTION_HORIZONTAL, $returnCellRef);
        } catch (SpreadsheetException) {
            // sheet or range of cells couldn't be loaded
            return [];
        }
    }

    /**
     * @param string $range Range of cells (i.e. "A1:B10"), or just one cell (i.e. "A1")
     * @param bool $returnCellRef False - Return array of rows/columns indexed by number counting from zero
     *                                  True - Return rows and columns indexed by their actual row and column IDs
     * @return array<array<ValueObject\CellDataValueObject>>
     * @throws SpreadsheetException
     */
    public function rangeToCellArray(
        Worksheet\Worksheet $sheet,
        string $range,
        string $direction = self::EXTRACT_DIRECTION_HORIZONTAL,
        bool $returnCellRef = false
    ): array {
        // Identify the range that we need to extract from the worksheet
        [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($this->rangeService->convert($sheet, $range));

        // set ignored cells, cell iterator range and iterator type to use (column or row)
        $cellArray = $direction === self::EXTRACT_DIRECTION_VERTICAL
            ? $this->processIteratorCellsWithCallback(
                $sheet->getColumnIterator(
                    Coordinate::stringFromColumnIndex($rangeStart[0]),
                    Coordinate::stringFromColumnIndex($rangeEnd[0])
                ),
                [(int)$rangeStart[1], (int)$rangeEnd[1]],
                $this->spanService->getIgnoredColumns($sheet),
                $this->spanService->getIgnoredCells($sheet),
                $this->spanService->getMergedCells($sheet)
            )
            : $this->processIteratorCellsWithCallback(
                $sheet->getRowIterator((int)$rangeStart[1], (int)$rangeEnd[1]),
                [Coordinate::stringFromColumnIndex($rangeStart[0]), Coordinate::stringFromColumnIndex($rangeEnd[0])],
                $this->spanService->getIgnoredRows($sheet),
                $this->spanService->getIgnoredCells($sheet),
                $this->spanService->getMergedCells($sheet)
            );

        if ($returnCellRef === true) {
            $cellArray = $this->updateColumnIndexesFromString($cellArray, $direction);
        }

        return $cellArray;
    }

    /**
     * @param Iterator<int|string, Row|Column> $iterator
     * @param array<int|string> $cellIteratorArgs
     * @param array<mixed> $ignoredCellLines
     * @param array<mixed> $ignoreCells
     * @param array<int|string, array<string, int|array<int>>> $mergedCells
     *
     * @return array<array<ValueObject\CellDataValueObject>>
     * @throws SpreadsheetException
     */
    private function processIteratorCellsWithCallback(
        Iterator $iterator,
        array $cellIteratorArgs = [],
        array $ignoredCellLines = [],
        array $ignoreCells = [],
        array $mergedCells = []
    ): array {
        $returnValue = [];
        foreach ($iterator as $line => $cells) {
            if (in_array($line, $ignoredCellLines, true)) {
                continue; // this row/column can be completely ignored
            }

            /** @var Worksheet\RowCellIterator|Worksheet\ColumnCellIterator $cellIterator */
            $cellIterator = $cells->getCellIterator(...$cellIteratorArgs);
            $cellIterator->setIterateOnlyExistingCells(false); // loop all cells ;)

            foreach ($cellIterator as $cellIndex => $cell) {
                $cellReference = $cellIterator instanceof Worksheet\ColumnCellIterator
                    ? $line . $cellIndex
                    : $cellIndex . $line;

                if (in_array($cellReference, $ignoreCells, true)) {
                    continue; // ignore processing of this cell
                }

                $getCellArgs = [$cell, $mergedCells[$cellReference] ?? []];
                if ($cellIterator instanceof Worksheet\ColumnCellIterator) {
                    // column-based $line should be string and cellIndex is row integer
                    $returnValue[$line][(int)$cellIndex] = $this->getCellValue(...$getCellArgs);
                } else {
                    // row-based $line is integer and cellIndex should be column string
                    $returnValue[(int)$line][$cellIndex] = $this->getCellValue(...$getCellArgs);
                }
            }
        }

        return $returnValue;
    }

    /**
     * @param array<string, int|array<int>> $mergeInformation
     * @throws SpreadsheetException
     */
    private function getCellValue(Cell $cell, array $mergeInformation = []): ValueObject\CellDataValueObject
    {
        $metaData = [];
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend() === true) {
            $alignment = $cell->getStyle()->getAlignment();

            // evaluate style classes for backend usage
            $metaData['backendCellClasses'][] = $this->mappingService->convertValue(
                'halign-backend',
                $alignment->getHorizontal(),
                $this->mappingService->convertValue('halign-backend-datatype', $cell->getDataType())
            );
            $metaData['backendCellClasses'][] = $this->mappingService->convertValue(
                'valign-backend',
                $alignment->getVertical()
            );

            // cleanup values if they are empty
            $metaData['backendCellClasses'] = array_filter($metaData['backendCellClasses']);
        }

        return ValueObject\CellDataValueObject::create(
            $cell,
            $this->cellService->getFormattedValue($cell),
            (int)($mergeInformation['rowspan'] ?? 0),
            (int)($mergeInformation['colspan'] ?? 0),
            (array)($mergeInformation['additionalStyleIndexes'] ?? []),
            $metaData
        );
    }

    /**
     * @param array<array<ValueObject\CellDataValueObject>> $cellArray
     *
     * @return array<array<ValueObject\CellDataValueObject>>
     */
    private function updateColumnIndexesFromString(
        array $cellArray,
        string $direction = self::EXTRACT_DIRECTION_HORIZONTAL
    ): array {
        if ($direction === self::EXTRACT_DIRECTION_VERTICAL) {
            // just get all keys and values for array combine
            // before combine map all keys to get column index from string
            return array_combine(
                array_map(
                    static fn ($key) => Coordinate::columnIndexFromString($key),
                    array_keys($cellArray)
                ),
                array_values($cellArray)
            );
        }

        // iterate all rows and do same column conversion as above
        foreach ($cellArray as $row => $columns) {
            $cellArray[$row] = array_combine(
                array_map(
                    static fn ($key) => Coordinate::columnIndexFromString($key),
                    array_keys($columns)
                ),
                array_values($columns)
            );
        }

        return $cellArray;
    }
}
