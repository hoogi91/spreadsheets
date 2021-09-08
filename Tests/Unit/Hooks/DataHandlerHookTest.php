<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Hooks;

use Hoogi91\Spreadsheets\Hooks\DataHandlerHook;
use Hoogi91\Spreadsheets\Tests\Unit\FileRepositoryMockTrait;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DataHandlerHookTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Hooks
 */
class DataHandlerHookTest extends UnitTestCase
{
    use FileRepositoryMockTrait;

    private const DATA_HANDLER_NEW_IDS = [
        'NEW123456' => 123456,
    ];

    public function setUp()
    {
        parent::setUp();
        // default record has no bodytext
        \Closure::bind(function () {
            self::$records[123456] = ['bodytext' => ''];
        }, null, DataHandlerHook::class)->call(new DataHandlerHook());
    }

    public function tearDown()
    {
        parent::tearDown();
        // reset data handler static property bindings
        \Closure::bind(function () {
            self::$records = [];
        }, null, DataHandlerHook::class)->call(new DataHandlerHook());
        gc_collect_cycles();
    }

    /**
     * @dataProvider datamapProvider
     */
    public function testProcessDatamapHook(
        array $hookParams,
        bool $updateTriggered = false,
        array $updateParams = []
    ): void {
        $dataHandlerMock = $this->createMock(DataHandler::class);
        $dataHandlerMock->substNEWwithIDs = self::DATA_HANDLER_NEW_IDS;
        if ($updateTriggered === true) {
            $dataHandlerMock->expects(self::once())->method('updateDB')->with(...array_values($updateParams));
        } else {
            $dataHandlerMock->expects(self::never())->method('updateDB');
        }

        // append data handler to hook params
        $hookParams[] = $dataHandlerMock;
        (new DataHandlerHook())->processDatamap_afterDatabaseOperations(...array_values($hookParams));
    }

    /**
     * @dataProvider datamapWithFileReferenceProvider
     */
    public function testProcessDatamapHookWithFileRelations(
        array $hookParams,
        array $references,
        bool $updateTriggered = false,
        array $updateParams = [],
        callable $closure = null
    ): void {
        // update file repository mock with given reference uid's
        $references = array_map(function ($reference) {
            $mock = $this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock();
            $mock->method('getUid')->willReturn($reference);
            return $mock;
        }, $references);
        $fileRepositoryMock = $this->getFileRepositoryMock();
        $fileRepositoryMock->method('findByRelation')->willReturn($references);

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getContainerMock();
        $container->method('get')->willReturn($fileRepositoryMock);
        GeneralUtility::setContainer($container);

        // update statically saved entries got with backend utility
        if ($closure !== null) {
            \Closure::bind($closure, null, DataHandlerHook::class)->call(new DataHandlerHook());
        }

        // now start process datamap hook test
        $this->testProcessDatamapHook($hookParams, $updateTriggered, $updateParams);
    }

    public function datamapProvider(): array
    {
        return [
            '[NEW/UPDATED] ID is not mapped or integer' => [
                'hookParams' => self::hookParams(['id' => 'NEW123abc']),
            ],
            '[NEW/UPDATED] is not in tt_content' => [
                'hookParams' => self::hookParams(['table' => 'pages']),
            ],
            '[NEW/UPDATED] is not in valid status' => [
                'hookParams' => self::hookParams(['status' => 'unknown']),
            ],
            '[NEW] is not a spreadsheet table' => [
                'hookParams' => self::hookParams(['fields' => ['CType' => 'textpic']]),
            ],
            '[UPDATED] is not a spreadsheet table' => [
                // force call backend utility which will return null on default
                'hookParams' => self::hookParams(['status' => 'update', 'fields' => ['CType' => null]]),
            ],
            '[NEW] has clean assets field' => [
                'hookParams' => self::hookParams(['fields' => ['tx_spreadsheets_assets' => 0]]),
            ],
            '[UPDATED] has clean assets field' => [
                'hookParams' => self::hookParams(['status' => 'update', 'fields' => ['tx_spreadsheets_assets' => 0]]),
                'updateTriggered' => true,
                'updateParams' => ['tt_content', 123456, ['bodytext' => '']],
            ],
        ];
    }

    public function datamapWithFileReferenceProvider(): array
    {
        return [
            '[NEW/UPDATED] file reference is not found' => [
                'hookParams' => self::hookParams(),
                'references' => [],
                'updateTriggered' => false,
                'updateParams' => [],
            ],
            '[NEW/UPDATED] bodytext is not empty' => [
                'hookParams' => self::hookParams(),
                'references' => [123],
                'updateTriggered' => false,
                'updateParams' => [],
                'closure' => function () {
                    self::$records[123456] = [
                        'CType' => 'spreadsheets_table',
                        'tx_spreadsheets_assets' => 1,
                        'bodytext' => 'spreadsheet://456',
                    ];
                },
            ],
            '[NEW] saved and bodytext gets updated' => [
                // uses file repo mock reference ID
                'hookParams' => self::hookParams(),
                'references' => [456],
                'updateTriggered' => true,
                'updateParams' => ['tt_content', 123456, ['bodytext' => 'spreadsheet://456']],
            ],
        ];
    }

    private static function hookParams(array $data = []): array
    {
        return [
            'status' => $data['status'] ?? 'new',
            'table' => $data['table'] ?? 'tt_content',
            'id' => $data['id'] ?? array_keys(self::DATA_HANDLER_NEW_IDS)[0],
            'fields' => array_replace_recursive(
                [
                    'CType' => 'spreadsheets_table',
                    'tx_spreadsheets_assets' => 1, // on default every request has one asset
                ],
                $data['fields'] ?? []
            ),
        ];
    }
}
