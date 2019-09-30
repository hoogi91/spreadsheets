<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Form\Element;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Phpoffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Hoogi91\Spreadsheets\Enum\HAlign;
use Hoogi91\Spreadsheets\Enum\VAlign;
use Hoogi91\Spreadsheets\Domain\Model\SpreadsheetValue;
use Hoogi91\Spreadsheets\Service\ReaderService;

/**
 * Class DataInputElement
 * @package Hoogi91\Spreadsheets\Form\Element
 */
class DataInputElement extends AbstractFormElement
{

    const DEFAULT_TEMPLATE_PATH = 'EXT:spreadsheets/Resources/Private/Templates/FormElement/DataInput.html';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $tca = [];

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * DataInputElement constructor.
     *
     * @param NodeFactory $nodeFactory
     * @param array       $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->params = $this->data['parameterArray'];
        $this->config = $this->params['fieldConf']['config'];
        $this->tca = $this->data['processedTca'];

        /** @var StandaloneView $view */
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplatePathAndFilename($this->getTemplatePath());
        $this->view->assign('inputSize', (int)$this->config['size'] ?: 0);
    }

    /**
     * @return string
     */
    protected function getTemplatePath()
    {
        if (empty($this->config['template'])) {
            return GeneralUtility::getFileAbsFileName(static::DEFAULT_TEMPLATE_PATH);
        }

        $templatePath = GeneralUtility::getFileAbsFileName($this->config['template']);
        if (!empty($templatePath) && is_file($templatePath)) {
            return $templatePath;
        }

        return GeneralUtility::getFileAbsFileName(static::DEFAULT_TEMPLATE_PATH);
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

        if (!array_key_exists($this->config['uploadField'], $this->tca['columns'])) {
            // upload fiels hasn't been specified
            $this->view->assign('missingUploadField', true);
        } elseif (empty($references = $this->getValidFileReferences($this->config['uploadField']))) {
            // return alert if non valid file references were uploaded
            $this->view->assign('nonValidReferences', true);
        } else {
            // register additional assets only when input will be rendered
            $this->registerAdditionalAssets($resultArray);

            // get spreadsheet data from all file references
            $sheetData = $this->getFileReferencesSpreadsheetData($references);

            $this->view->assignMultiple([
                'items'                 => $references,
                'sheetData'             => json_encode($sheetData),
                'sheetsOnly'            => (bool)$this->config['sheetsOnly'],
                'allowColumnExtraction' => (bool)$this->config['allowColumnExtraction'],
                'inputName'             => $this->params['itemFormElName'],
                'inputNameHash'         => md5($this->params['itemFormElName']),
                'valueObject'           => SpreadsheetValue::createFromDatabaseString(
                    $this->params['itemFormElValue'],
                    $sheetData
                ),
            ]);
        }

        // render view and return result array
        $resultArray['html'] = $this->view->render();
        return $resultArray;
    }

    /**
     * add HandsOnTable to RequireJS backend configuration
     *
     * @param $resultArray
     */
    protected function registerAdditionalAssets(&$resultArray)
    {
        // add own requireJS module that uses above dependency and addtional styling for handsontable
        $resultArray['requireJsModules'] = ['TYPO3/CMS/Spreadsheets/SpreadsheetDataInput'];
        $resultArray['stylesheetFiles'] = [
            'EXT:spreadsheets/Resources/Public/Css/HandsOnTable/handsontable.full.min.css',
        ];
    }

    /**
     * @param string $fieldName
     *
     * @return array
     */
    protected function getValidFileReferences(string $fieldName): array
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
        $references = array_filter($references, function ($reference) {
            /** @var FileReference $reference */
            return in_array($reference->getExtension(), ReaderService::ALLOWED_EXTENSTIONS);
        });

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
    protected function getFileReferencesSpreadsheetData(array $references): array
    {
        if (empty($references)) {
            return [];
        }

        // get data from file references
        $sheetData = [];
        $reference = array_shift($references);
        while ($reference instanceof FileReference) {
            try {
                /** @var ReaderService $readerService */
                $readerService = GeneralUtility::makeInstance(ReaderService::class, $reference);
                foreach ($readerService->getSheets() as $sheet) {
                    try {
                        $referenceUid = $reference->getUid();
                        $sheetIndex = $readerService->getSpreadsheet()->getIndex($sheet);

                        $sheetData[$referenceUid][$sheetIndex] = [
                            'name'      => $sheet->getTitle(),
                            'data'      => $sheet->toArray(),
                            'metaData'  => $this->getCellStyles($sheet),
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
        array_walk_recursive($sheetData, function (&$item) {
            if (is_string($item) && !mb_detect_encoding($item, 'utf-8', true)) {
                $item = utf8_encode($item);
            }
        });
        return $sheetData;
    }

    /**
     * @param Worksheet $sheet
     *
     * @return array
     * @throws SpreadsheetException
     */
    protected function getCellStyles(Worksheet $sheet): array
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
                    $horizontalClass = HAlign::mapHandsOnTable(
                        $cellStyle->getAlignment()->getHorizontal(),
                        $cell->getDataType()
                    );
                    $verticalClass = VAlign::mapHandsOnTable(
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
     * @param Worksheet $sheet
     *
     * @return array
     */
    protected function getMergeCells(Worksheet $sheet): array
    {
        return array_values(array_map(function ($cells) {
            list($cells) = Coordinate::splitRange($cells);
            list($startColumn, $startRow) = Coordinate::coordinateFromString($cells[0]);
            list($endColumn, $endRow) = Coordinate::coordinateFromString($cells[1]);

            $startIndex = Coordinate::columnIndexFromString($startColumn);
            $endIndex = Coordinate::columnIndexFromString($endColumn);
            return [
                'row'     => (int)$startRow - 1,
                'col'     => $startIndex - 1,
                'rowspan' => (int)$endRow - $startRow + 1,
                'colspan' => $endIndex - $startIndex + 1,
            ];
        }, $sheet->getMergeCells()));
    }
}
