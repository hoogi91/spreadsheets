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
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
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
        // this file does not exists
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
        'requireJsModules' => [],
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

    private ReaderService&MockObject $readerService;

    private ExtractorService&MockObject $extractorService;

    private MockObject&StandaloneView $standaloneView;

    private MockObject&IconFactory $iconFactory;

    private MockObject&RelationHandler $relationHandler;

    private MockObject&ResourceFactory $resourceFactory;

    /**
     * @var array<mixed>
     */
    private static array $assignedVariables = [];

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        $trueCallback = static fn (callable $callback) => self::callback(
            static fn () => call_user_func_array($callback, func_get_args()) !== false
        );

        parent::setUp();
        $spreadsheet = (new Xlsx())->load(dirname(__DIR__, 3) . '/Fixtures/01_fixture.xlsx');
        $this->readerService = $this->createMock(ReaderService::class);
        $this->readerService->method('getSpreadsheet')->willReturn($spreadsheet);
        $this->standaloneView = $this->createMock(StandaloneView::class);
        $this->standaloneView->method('assign')->with(
            $trueCallback(static fn ($key) => self::$assignedVariables['_next'] = $key),
            $trueCallback(static function ($value): void {
                self::$assignedVariables[self::$assignedVariables['_next']] = $value;
                unset(self::$assignedVariables['_next']);
            })
        )->willReturnSelf();
        $this->standaloneView->method('assignMultiple')->with($trueCallback(
            static fn ($values) => self::$assignedVariables = array_merge(self::$assignedVariables, $values)
        ))->willReturnSelf();
        $this->standaloneView->method('render')->willReturnCallback(static fn () => self::$assignedVariables);
        $this->extractorService = $this->createMock(ExtractorService::class);
        $this->extractorService->method('rangeToCellArray')->willReturn([
            'A1' => file_get_contents(dirname(__DIR__, 3) . '/Fixtures/latin1-content.txt'),
        ]);
        $this->iconFactory = $this->createMock(IconFactory::class);

        GeneralUtility::addInstance(ReaderService::class, $this->readerService);
        GeneralUtility::addInstance(ExtractorService::class, $this->extractorService);
        GeneralUtility::addInstance(StandaloneView::class, $this->standaloneView);
        GeneralUtility::addInstance(IconFactory::class, $this->iconFactory);

        // mock file reference handler to get valid files
        $this->relationHandler = $this->createMock(RelationHandler::class);
        $this->resourceFactory = $this->createMock(ResourceFactory::class);

        GeneralUtility::addInstance(RelationHandler::class, $this->relationHandler);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $this->resourceFactory);

        // setup extension TCA
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] = [];
        include dirname(__DIR__, 4) . '/Configuration/TCA/Overrides/tt_content.php';
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
        // setup relation and resource factory to return file reference
        $dbData = (array)($data['databaseRow'] ?? []);
        $referenceFieldUid = MathUtility::canBeInterpretedAsInteger($dbData[self::DEFAULT_UPLOAD_FIELD] ?? null)
            ? (int) $dbData[self::DEFAULT_UPLOAD_FIELD]
            : null;
        $referenceFileExtension = self::FILE_REFERENCE_TYPE_MAP[$referenceFieldUid] ?? 'xlsx';
        if ($referenceFileExtension !== 'null') {
            $this->relationHandler->tableArray = [
                'sys_file_reference' => [$referenceFieldUid], // mocked file reference uid to spreadsheet file
            ];
            $this->resourceFactory->method('getFileReferenceObject')->willReturn(
                $this->createConfiguredMock(
                    FileReference::class,
                    [
                        'getUid' => $referenceFieldUid,
                        'getExtension' => str_contains($referenceFileExtension, '|exception')
                            ? strtok($referenceFileExtension, '|')
                            : $referenceFileExtension,
                        'toArray' => [
                            'ext' => str_contains($referenceFileExtension, '|exception')
                            ? strtok($referenceFileExtension, '|')
                            : $referenceFileExtension,
                        ],
                    ]
                )
            );

            if (str_contains($referenceFileExtension, '|exceptionRead')) {
                $this->readerService->method('getSpreadsheet')->willThrowException(new ReaderException());
            }
            if (str_contains($referenceFileExtension, '|exceptionCell')) {
                $this->extractorService->method('rangeToCellArray')->willThrowException(new SpreadsheetException());
            }
        } else {
            // no file references exists
            $this->relationHandler->tableArray = [
                'sys_file_reference' => [],
            ];
        }

        // extend config and create element
        $data['parameterArray']['fieldConf']['config'] = $fieldConfig;
        $element = new DataInputElement($this->createMock(NodeFactory::class), $data);

        // extract mocked html variables from rendered data
        $renderedData = $element->render();
        $htmlData = (array)($renderedData['html'] ?? null);
        unset($renderedData['html']);

        $expectedResult = self::EMPTY_EXPECTED_RESULT;
        if ((new Typo3Version())->getMajorVersion() > 11) {
            $expectedResult['javaScriptModules'] = [];
        }

        if (isset($expected['valueObject'])) {
            $expectedResult['stylesheetFiles'] = ['EXT:spreadsheets/Resources/Public/Css/SpreadsheetDataInput.css'];
            $expectedResult['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
                'TYPO3/CMS/Spreadsheets/SpreadsheetDataInput'
            )->instance($expected['inputName'] ?? null);
            self::assertEquals($expectedResult, $renderedData);
        } else {
            // no value object means we should have an empty form element result
            self::assertEquals($expectedResult, $renderedData);
        }

        // create comparable array
        array_walk_recursive(
            $htmlData,
            static function (&$item): void {
                if ($item instanceof JsonSerializable) {
                    $item = $item->jsonSerialize();
                }
                if (is_object($item) && method_exists($item, 'toArray')) {
                    $item = $item->toArray();
                }
            }
        );
        self::assertEquals($expected, $htmlData);
    }

    /**
     * @return Traversable<string, array<string, mixed>>
     */
    public function renderDataProvider(): Traversable
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

        yield 'missing valid upload reference' => [
            'expected' => ['inputSize' => 100, 'nonValidReferences' => true],
            'data' => $dataBuilder(589),
        ];

        yield 'invalid DSN found' => [
            'expected' => ['inputName' => null, 'valueObject' => ''] + self::DEFAULT_EXPECTED_HTML_DATA,
            'data' => ['parameterArray' => null] + self::DEFAULT_DATA,
        ];

        yield 'spreadsheet read exception' => [
            'expected' => array_replace(
                self::DEFAULT_EXPECTED_HTML_DATA,
                [
                    'sheetFiles' => [678 => ['ext' => 'html']],
                    'sheetData' => [],
                    'valueObject' => 'spreadsheet://678?index=1&range=D2%3AG5&direction=vertical',
                ]
            ),
            'data' => $dataBuilder(678),
        ];

        yield 'spreadsheet range to cell array exception' => [
            'expected' => array_replace(
                self::DEFAULT_EXPECTED_HTML_DATA,
                [
                    'sheetFiles' => [679 => ['ext' => 'csv']],
                    'sheetData' => [679 => []], // because of extraction exception this file sheets are empty
                    'valueObject' => 'spreadsheet://679?index=1&range=D2%3AG5&direction=vertical',
                ]
            ),
            'data' => $dataBuilder(679),
        ];

        yield 'successful input element rendering' => [];

        $templateBuilder = static fn (string $template) => [
            'expected' => array_replace_recursive(
                self::DEFAULT_EXPECTED_HTML_DATA,
                ['config' => ['template' => $template]]
            ),
            'data' => self::DEFAULT_DATA,
            'fieldConfig' => ['template' => $template] + self::DEFAULT_FIELD_CONF,
        ];

        yield 'successful input element rendering with custom template path' => $templateBuilder(
            'EXT:spreadsheets/Resources/Private/Templates/FormElement/DataInput.html'
        );

        yield 'successful input element rendering with unknown template path' => $templateBuilder(
            'EXT:spreadsheets/Resources/Private/Templates/FormElement/ThisFileDoesNotExists.html'
        );
    }
}
