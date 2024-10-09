<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\Form\Element;

use Hoogi91\Spreadsheets\Form\Element\DataInputElement;
use Hoogi91\Spreadsheets\Service\ExtractorService;
use Hoogi91\Spreadsheets\Service\ReaderService;
use JsonSerializable;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PHPUnit\Framework\MockObject\MockObject;
use Traversable;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DataInputElementTest extends UnitTestCase
{
    private const DEFAULT_UPLOAD_FIELD = 'tx_spreadsheets_assets';
    private const DEFAULT_FIELD_CONF = [
        'renderType' => 'spreadsheetInput',
        'uploadField' => self::DEFAULT_UPLOAD_FIELD,
        'sheetsOnly' => true,
        'size' => 100,
    ];

    private const FILE_REFERENCE_TYPE_MAP = [
        0 => 'null',
        // this file does not exist
        465 => 'xlsx',
        // should work
        589 => 'pdf',
        // should fail
        678 => 'html|exceptionRead', // file reference says html but reader will throw exception
        // should fail on read
        679 => 'csv|exceptionCell', // file reference says csv but rangeToCellArray will throw exception
    ];

    private const DEFAULT_DATA = [
        'tableName' => 'tt_content',
        'databaseRow' => [
            'uid' => 1,
            self::DEFAULT_UPLOAD_FIELD => 465,
        ],
        'processedTca' => [
            'columns' => [
                self::DEFAULT_UPLOAD_FIELD => [ /* any field configuration goes here */],
            ],
        ],
        'parameterArray' => [
            'itemFormElName' => 'my-form-identifier',
            'itemFormElValue' => 'spreadsheet://465?index=1&range=D2%3AG5&direction=vertical',
        ],
    ];

    private const EMPTY_EXPECTED_RESULT = [
        'additionalJavaScriptPost' => [],
        'additionalHiddenFields' => [],
        'additionalInlineLanguageLabelFiles' => [],
        'stylesheetFiles' => [],
        'javaScriptModules' => [],
        'inlineData' => [],
    ];

    private const DEFAULT_EXPECTED_HTML_DATA = [
        'inputSize' => 100,
        'inputName' => 'my-form-identifier',
        'config' => [
            'renderType' => 'spreadsheetInput',
            'uploadField' => 'tx_spreadsheets_assets',
            'sheetsOnly' => true,
            'size' => 100,
        ],
        'sheetFiles' => [
            465 => ['ext' => 'xlsx'],
        ],
        'sheetData' => [
            465 => [
                ['name' => 'Fixture1', 'cells' => ['A1' => 'Hírek']],
                ['name' => 'Fixture2', 'cells' => ['A1' => 'Hírek']],
            ],
        ],
        'valueObject' => 'spreadsheet://465?index=1&range=D2%3AG5&direction=vertical',
    ];

    private ReaderService $readerService;
    private ExtractorService $extractorService;
    private StandaloneView $standaloneView;
    private IconFactory $iconFactory;
    private ResourceFactory $resourceFactory;

    /**
     * @var array<mixed>
     */
    private static array $assignedVariables = [];

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock services
        $this->readerService = $this->createMock(ReaderService::class);
        $this->extractorService = $this->createMock(ExtractorService::class);
        $this->standaloneView = $this->createMock(StandaloneView::class);
        $this->iconFactory = $this->createMock(IconFactory::class);
        $this->resourceFactory = $this->createMock(ResourceFactory::class);

        // Register mocks
        GeneralUtility::addInstance(ReaderService::class, $this->readerService);
        GeneralUtility::addInstance(ExtractorService::class, $this->extractorService);
        GeneralUtility::addInstance(StandaloneView::class, $this->standaloneView);
        GeneralUtility::addInstance(IconFactory::class, $this->iconFactory);
        GeneralUtility::addInstance(ResourceFactory::class, $this->resourceFactory);

        // Setup expectations for StandaloneView
        $this->standaloneView->method('assign')->willReturnSelf();
        $this->standaloneView->method('assignMultiple')->willReturnSelf();
        $this->standaloneView->method('render')->willReturnCallback(function () {
            return self::$assignedVariables;
        });

        // Load the spreadsheet fixture
        $spreadsheet = (new Xlsx())->load(dirname(__DIR__, 3) . '/Fixtures/01_fixture.xlsx');
        $this->readerService->method('getSpreadsheet')->willReturn($spreadsheet);

        $this->extractorService->method('rangeToCellArray')->willReturn([
            'A1' => file_get_contents(dirname(__DIR__, 3) . '/Fixtures/latin1-content.txt'),
        ]);

        // Mock FileReference
        $fileReferenceMock = $this->createMock(FileReference::class);
        $fileReferenceMock->method('getUid')->willReturn(465);
        $fileReferenceMock->method('getExtension')->willReturn('xlsx');
        $fileReferenceMock->method('toArray')->willReturn(['ext' => 'xlsx']);

        // Setup ResourceFactory to return the mock FileReference
        $this->resourceFactory->method('getFileReferenceObject')->willReturn($fileReferenceMock);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
        self::$assignedVariables = [];
    }

    /**
     * @dataProvider renderDataProvider
     *
     * @param array<mixed> $expected
     * @param array<mixed> $data
     * @param array<mixed> $fieldConfig
     */
    public function testRendering(
        array $expected = self::DEFAULT_EXPECTED_HTML_DATA,
        array $data = self::DEFAULT_DATA,
        array $fieldConfig = self::DEFAULT_FIELD_CONF
    ): void {
        // Adjust data
        $data['parameterArray']['fieldConf']['config'] = $fieldConfig;

        // Create instance of the element
        $element = GeneralUtility::makeInstance(DataInputElement::class);
        $element->setData($data);

        // Render the element
        $renderedData = $element->render();

        // Adjust expected result
        $expectedResult = self::EMPTY_EXPECTED_RESULT;

        if (isset($expected['valueObject'])) {
            $expectedResult['stylesheetFiles'] = ['EXT:spreadsheets/Resources/Public/Css/SpreadsheetDataInput.css'];
            $expectedResult['javaScriptModules'] = ['@vendor/my-extension/SpreadsheetDataInput.js'];
        }

        // Extract mocked HTML variables from rendered data
        $htmlData = (array)($renderedData['html'] ?? []);
        unset($renderedData['html']);

        // Compare the results
        self::assertEquals($expectedResult, $renderedData);
        self::assertEquals($expected, $htmlData);
    }

    /**
     * @return Traversable<string, array<string, mixed>>
     */
    public static function renderDataProvider(): Traversable
    {
        $dataBuilder = static fn (int $type) => array_replace_recursive(
            self::DEFAULT_DATA,
            [
                'databaseRow' => ['uid' => 1, self::DEFAULT_UPLOAD_FIELD => $type],
                'parameterArray' => [
                    'itemFormElValue' => 'spreadsheet://' . $type . '?index=1&range=D2%3AG5&direction=vertical',
                ],
            ]
        );

        yield 'missing upload field' => [
            'expected' => ['inputSize' => 100, 'missingUploadField' => true],
            'data' => ['processedTca' => null] + self::DEFAULT_DATA,
        ];

        yield 'empty references' => [
            'expected' => ['inputSize' => 100, 'nonValidReferences' => true],
            'data' => ['databaseRow' => ['uid' => 1, self::DEFAULT_UPLOAD_FIELD => 0]] + self::DEFAULT_DATA,
        ];

        yield 'successful input element rendering' => [];

        // Add more test cases as needed...
    }
}
