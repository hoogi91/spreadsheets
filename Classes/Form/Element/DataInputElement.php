<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Form\Element;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use Hoogi91\Spreadsheets\Service\ReaderService;
use Hoogi91\Spreadsheets\Service\ValueMappingService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class DataInputElement
 * @package Hoogi91\Spreadsheets\Form\Element
 */
class DataInputElement extends AbstractFormElement
{

    private const DEFAULT_TEMPLATE_PATH = 'EXT:spreadsheets/Resources/Private/Templates/FormElement/DataInput.html';

    /**
     * @var ReaderService
     */
    private $readerService;

    /**
     * @var ValueMappingService
     */
    private $mappingService;

    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $tca;

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * DataInputElement constructor.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->readerService = GeneralUtility::makeInstance(ReaderService::class);
        $this->mappingService = GeneralUtility::makeInstance(ValueMappingService::class);

        $this->params = $this->data['parameterArray'];
        $this->config = $this->params['fieldConf']['config'];
        $this->tca = $this->data['processedTca'];

        /** @var StandaloneView $view */
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplatePathAndFilename($this->getTemplatePath());
        $this->view->assign('inputSize', (int)$this->config['size'] ?: 0);
    }

    /**
     * This will render a single-line input password field
     * and a button to toggle password visibility
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        // get initialize result array from parent abstract node
        $resultArray = $this->initializeResultArray();

        // upload fields hasn't been specified
        if (array_key_exists($this->config['uploadField'], $this->tca['columns']) === false) {
            $resultArray['html'] = $this->view->assign('missingUploadField', true)->render();
            return $resultArray;
        }

        // return alert if non valid file references were uploaded
        $references = $this->getValidFileReferences($this->config['uploadField']);
        if (empty($references)) {
            $resultArray['html'] = $this->view->assign('nonValidReferences', true)->render();
            return $resultArray;
        }

        // register additional assets only when input will be rendered
        // add own requireJS module that uses above dependency and additional styling for handsontable
        $resultArray['requireJsModules'] = ['TYPO3/CMS/Spreadsheets/SpreadsheetDataInput'];
        $resultArray['stylesheetFiles'] = [
            'EXT:spreadsheets/Resources/Public/Css/HandsOnTable/handsontable.full.min.css',
        ];

        try {
            $valueObject = DsnValueObject::createFromDSN($this->params['itemFormElValue']);
        } catch (InvalidDataSourceNameException $exception) {
            $valueObject = '';
        }

        $this->view->assignMultiple(
            [
                'items' => $references,
                'sheetData' => json_encode($this->getFileReferencesSpreadsheetData($references)),
                'sheetsOnly' => (bool)$this->config['sheetsOnly'],
                'allowColumnExtraction' => (bool)$this->config['allowColumnExtraction'],
                'inputName' => $this->params['itemFormElName'],
                'inputNameHash' => md5($this->params['itemFormElName']),
                'valueObject' => $valueObject,
            ]
        );

        // render view and return result array
        $resultArray['html'] = $this->view->render();
        return $resultArray;
    }

    /**
     * @return string
     */
    private function getTemplatePath(): string
    {
        if (empty($this->config['template'])) {
            return GeneralUtility::getFileAbsFileName(static::DEFAULT_TEMPLATE_PATH);
        }

        $templatePath = GeneralUtility::getFileAbsFileName($this->config['template']);
        if (is_file($templatePath) === false) {
            return GeneralUtility::getFileAbsFileName(static::DEFAULT_TEMPLATE_PATH);
        }

        return $templatePath;
    }

    /**
     * @param string $fieldName
     *
     * @return array
     */
    private function getValidFileReferences(string $fieldName): array
    {
        $references = BackendUtility::resolveFileReferences(
            $this->data['tableName'],
            $fieldName,
            $this->data['databaseRow']
        );
        if (empty($references)) {
            return [];
        }

        // filter references by allowed types
        $references = array_filter(
            $references,
            static function ($reference) {
                /** @var FileReference $reference */
                return in_array($reference->getExtension(), ReaderService::ALLOWED_EXTENSIONS, true);
            }
        );

        // update key values of file references
        foreach ($references as $key => $reference) {
            $references['file:' . $key] = $reference;
            unset($references[$key]);
        }
        return $references;
    }

    /**
     * @param FileReference[] $references
     *
     * @return array
     */
    private function getFileReferencesSpreadsheetData(array $references): array
    {
        if (empty($references)) {
            return [];
        }

        // get data from file references
        $sheetData = [];
        $reference = array_shift($references);
        while ($reference instanceof FileReference) {
            try {
                foreach ($this->readerService->getSpreadsheet($reference)->getAllSheets() as $sheet) {
                    try {
                        $sheetIndex = $sheet->getParent()->getIndex($sheet);
                        $sheetData[$reference->getUid()][$sheetIndex] = [
                            'name' => $sheet->getTitle(),
                            'data' => $sheet->toArray(),
                            'metaData' => $this->getCellStyles($sheet),
                            'mergeData' => $this->getMergeCells($sheet),
                        ];
                    } catch (SpreadsheetException $e) {
                        // ignore sheet when an exception occurs
                    }
                }
            } catch (ReaderException $e) {
                // ignore reading non-existing or invalid file reference
            }
            $reference = array_shift($references);
        }

        // convert whole sheet data content to UTF-8
        array_walk_recursive(
            $sheetData,
            static function (&$item) {
                if (is_string($item) && !mb_detect_encoding($item, 'utf-8', true)) {
                    $item = utf8_encode($item);
                }
            }
        );
        return $sheetData;
    }

    /**
     * @param Worksheet $sheet
     *
     * @return array
     * @throws SpreadsheetException
     */
    private function getCellStyles(Worksheet $sheet): array
    {
        $metaData = [];
        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // get all cells
            foreach ($cellIterator as $cell) {
                // get cell style by style index
                $cellStyle = $sheet->getParent()->getCellXfByIndex($cell->getXfIndex());

                // try to find alignment classes
                $horizontalClass = $verticalClass = '';
                if ($cellStyle instanceof Style) {
                    // set default mapping based on type
                    $horizontalClass = $this->mappingService->convertValue(
                        'halign-handsontable',
                        $cellStyle->getAlignment()->getHorizontal(),
                        $this->getDefaultHorizontalClassByCellType($cell->getDataType())
                    );


                    $verticalClass = $this->mappingService->convertValue(
                        'valign-handsontable',
                        $cellStyle->getAlignment()->getVertical()
                    );
                }

                $metaData[trim($horizontalClass . ' ' . $verticalClass)][] = [
                    'row' => ($cell->getRow() - 1),
                    'col' => Coordinate::columnIndexFromString($cell->getColumn()) - 1,
                ];
            }
        }
        return $metaData;
    }

    /**
     * @param string $dataType
     * @return string|null
     */
    private function getDefaultHorizontalClassByCellType(string $dataType): ?string
    {
        switch ($dataType) {
            case DataType::TYPE_BOOL:
            case DataType::TYPE_ERROR:
                return 'htCenter';
            case DataType::TYPE_FORMULA:
            case DataType::TYPE_NUMERIC:
                return 'htRight';
            default:
                return null;
        }
    }

    /**
     * @param Worksheet $sheet
     *
     * @return array
     */
    private function getMergeCells(Worksheet $sheet): array
    {
        return array_values(
            array_map(
                static function (string $cells) {
                    $coordinates = explode(':', $cells, 2);
                    [$startColumn, $startRow] = Coordinate::coordinateFromString($coordinates[0]);
                    [$endColumn, $endRow] = Coordinate::coordinateFromString($coordinates[1]);

                    $startIndex = Coordinate::columnIndexFromString($startColumn);
                    $endIndex = Coordinate::columnIndexFromString($endColumn);
                    return [
                        'row' => (int)$startRow - 1,
                        'col' => $startIndex - 1,
                        'rowspan' => (int)$endRow - (int)$startRow + 1,
                        'colspan' => $endIndex - $startIndex + 1,
                    ];
                },
                $sheet->getMergeCells()
            )
        );
    }
}
