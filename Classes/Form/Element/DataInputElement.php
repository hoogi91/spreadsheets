<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Form\Element;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use Hoogi91\Spreadsheets\Service\ExtractorService;
use Hoogi91\Spreadsheets\Service\ReaderService;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class DataInputElement extends AbstractFormElement
{
    private const DEFAULT_TEMPLATE_PATH = 'EXT:spreadsheets/Resources/Private/Templates/FormElement/DataInput.html';

    private readonly ReaderService $readerService;

    private readonly ExtractorService $extractorService;

    /**
     * @var array<string, string>
     */
    private array $config;

    private readonly StandaloneView $view;

    /**
     * @param array<mixed> $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->readerService = GeneralUtility::makeInstance(ReaderService::class);
        $this->extractorService = GeneralUtility::makeInstance(ExtractorService::class);
        $this->config = $this->data['parameterArray']['fieldConf']['config'] ?? [];

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplatePathAndFilename($this->getTemplatePath());
        $this->view->assign('inputSize', (int)($this->config['size'] ?? 0));
    }

    /**
     * @return array<mixed> As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        // get initialize result array from parent abstract node
        $resultArray = $this->initializeResultArray();

        // upload fields hasn't been specified
        if (array_key_exists($this->config['uploadField'], $this->data['processedTca']['columns'] ?? []) === false) {
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
        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
            'TYPO3/CMS/Spreadsheets/SpreadsheetDataInput'
        )->instance($this->data['parameterArray']['itemFormElName'] ?? null);
        $resultArray['stylesheetFiles'] = ['EXT:spreadsheets/Resources/Public/Css/SpreadsheetDataInput.css'];

        try {
            $valueObject = DsnValueObject::createFromDSN($this->data['parameterArray']['itemFormElValue'] ?? '');
        } catch (InvalidDataSourceNameException) {
            $valueObject = '';
        }

        $this->view->assignMultiple(
            [
                'inputName' => $this->data['parameterArray']['itemFormElName'] ?? null,
                'config' => $this->config,
                'sheetFiles' => $references,
                'sheetData' => $this->getFileReferencesSpreadsheetData($references),
                'valueObject' => $valueObject,
            ]
        );

        // render view and return result array
        $resultArray['html'] = $this->view->render();

        return $resultArray;
    }

    private function getTemplatePath(): string
    {
        if (empty($this->config['template'])) {
            return GeneralUtility::getFileAbsFileName(self::DEFAULT_TEMPLATE_PATH);
        }

        $templatePath = GeneralUtility::getFileAbsFileName($this->config['template']);
        if (is_file($templatePath) === false) {
            return GeneralUtility::getFileAbsFileName(self::DEFAULT_TEMPLATE_PATH);
        }

        return $templatePath;
    }

    /**
     * @return array<FileReference>
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
        return array_filter(
            $references,
            static fn ($reference) => in_array($reference->getExtension(), ReaderService::ALLOWED_EXTENSIONS, true)
        );
    }

    /**
     * @param array<FileReference> $references
     * @return array<mixed>
     */
    private function getFileReferencesSpreadsheetData(array $references): array
    {
        // read all spreadsheet from valid file references and filter out invalid references
        $spreadsheets = $this->getSpreadsheetsByFileReferences($references);

        // get data from file references
        $sheetData = [];
        foreach ($spreadsheets as $fileUid => $spreadsheet) {
            $sheetData[$fileUid] = $this->getWorksheetDataFromSpreadsheet($spreadsheet);
        }

        // convert whole sheet data content to UTF-8
        array_walk_recursive(
            $sheetData,
            static function (&$item): void {
                $item = is_string($item) && mb_detect_encoding($item, 'UTF-8', true) === false
                    ? mb_convert_encoding($item, 'UTF-8', mb_list_encodings())
                    : $item;
            }
        );

        return $sheetData;
    }

    /**
     * @param array<FileReference> $references
     * @return array<Spreadsheet>
     */
    private function getSpreadsheetsByFileReferences(array $references): array
    {
        $spreadsheets = [];
        foreach ($references as $reference) {
            try {
                $spreadsheets[$reference->getUid()] = $this->readerService->getSpreadsheet($reference);
            } catch (ReaderException) {
                // ignore reading non-existing or invalid file reference
            }
        }

        return $spreadsheets;
    }

    /**
     * @return array<mixed>
     */
    private function getWorksheetDataFromSpreadsheet(Spreadsheet $spreadsheet): array
    {
        $sheetData = [];
        foreach ($spreadsheet->getAllSheets() as $sheetIndex => $worksheet) {
            try {
                $worksheetRange = 'A1:' . $worksheet->getHighestColumn() . $worksheet->getHighestRow();
                $sheetData[$sheetIndex] = [
                    'name' => $worksheet->getTitle(),
                    'cells' => $this->extractorService->rangeToCellArray($worksheet, $worksheetRange),
                ];
            } catch (SpreadsheetException) {
                // ignore sheet when an exception occurs
            }
        }

        return $sheetData;
    }
}
