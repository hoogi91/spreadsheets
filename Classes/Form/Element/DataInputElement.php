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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class DataInputElement extends AbstractFormElement
{
    private const DEFAULT_TEMPLATE_PATH = 'EXT:spreadsheets/Resources/Private/Templates/FormElement/DataInput.html';

    private ReaderService $readerService;
    private ExtractorService $extractorService;
    private StandaloneView $view;

    /**
     * @var array<string, string>
     */
    private array $config = [];

    public function __construct(
        ReaderService $readerService,
        ExtractorService $extractorService,
        StandaloneView $view
    ) {
        $this->readerService = $readerService;
        $this->extractorService = $extractorService;
        $this->view = $view;
    }
    public function setData(array $data): void
    {
        $this->data = $data;
    }
    /**
     * @return array<mixed> As defined in initializeResultArray() of AbstractFormElement
     */
    public function render(): array
    {
        // Access the $data array
        $data = $this->data;

        // Initialize the result array
        $resultArray = $this->initializeResultArray();

        // Initialize configuration
        $this->config = $data['parameterArray']['fieldConf']['config'] ?? [];

        // Set the template path
        $this->view->setTemplatePathAndFilename($this->getTemplatePath());
        $this->view->assign('inputSize', (int)($this->config['size'] ?? 0));

        // Check if upload field is specified
        if (!isset($this->config['uploadField']) || !array_key_exists($this->config['uploadField'], $data['processedTca']['columns'] ?? [])) {
            $resultArray['html'] = $this->view->assign('missingUploadField', true)->render();

            return $resultArray;
        }

        // Get valid file references
        $references = $this->getValidFileReferences($this->config['uploadField']);
        if (empty($references)) {
            $resultArray['html'] = $this->view->assign('nonValidReferences', true)->render();

            return $resultArray;
        }

        // Register additional assets only when input will be rendered
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@hoogi91/spreadsheets/SpreadsheetDataInput.js');
        $pageRenderer->addCssFile('EXT:spreadsheets/Resources/Public/Css/SpreadsheetDataInput.css');

        try {
            $valueObject = DsnValueObject::createFromDSN($data['parameterArray']['itemFormElValue'] ?? '');
        } catch (InvalidDataSourceNameException) {
            $valueObject = '';
        }

        $this->view->assignMultiple(
            [
                'inputName' => $data['parameterArray']['itemFormElName'] ?? null,
                'config' => $this->config,
                'sheetFiles' => $references,
                'sheetData' => $this->getFileReferencesSpreadsheetData($references),
                'valueObject' => $valueObject,
            ]
        );

        // Render view and return result array
        $resultArray['html'] = $this->view->render();

        return $resultArray;
    }

    private function getTemplatePath(): string
    {
        if (empty($this->config['template'])) {
            return GeneralUtility::getFileAbsFileName(self::DEFAULT_TEMPLATE_PATH);
        }

        $templatePath = GeneralUtility::getFileAbsFileName($this->config['template']);
        if (!is_file($templatePath)) {
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

        // Filter references by allowed types
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
        // Read all spreadsheets from valid file references and filter out invalid references
        $spreadsheets = $this->getSpreadsheetsByFileReferences($references);

        // Get data from file references
        $sheetData = [];
        foreach ($spreadsheets as $fileUid => $spreadsheet) {
            $sheetData[$fileUid] = $this->getWorksheetDataFromSpreadsheet($spreadsheet);
        }

        // Convert whole sheet data content to UTF-8
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
                // Ignore reading non-existing or invalid file reference
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
                // Ignore sheet when an exception occurs
            }
        }

        return $sheetData;
    }
}
