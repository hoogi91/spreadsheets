<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Utility;

use Hoogi91\Spreadsheets\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ExtensionManagementUtilityTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Utility
 */
class ExtensionManagementUtilityTest extends UnitTestCase
{
    /**
     * @var array
     */
    private static $elementToInsert = [
        'Title of Plugin/Element',
        'element_key',
        'icon-identifier-of-element',
    ];

    /**
     * initialize with typical TCA structure of tt_content's CType column
     */
    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config'] = [
            'items' => [
                [
                    'Title of existing Plugin/Element',
                    'existing_element_key',
                    'icon-identifier-of-existing-element',
                ],
            ],
        ];
    }

    public function testInsertOnCType(): void
    {
        // execute insert twice to see item is added once
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert);
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert);

        // test expectations
        self::assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        self::assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][2][1]
        );
        self::assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
    }

    public function testInsertOnCTypeBeforeSpecificElement(): void
    {
        // execute insert
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert, 'before:existing_element_key');

        // test expectations
        self::assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        self::assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
        self::assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][1][1]
        );
    }

    public function testInsertOnCTypeAfterSpecificElement(): void
    {
        // execute insert
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert, 'after:existing_element_key');

        // test expectations
        self::assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        self::assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][1][1]
        );
        self::assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
    }

    public function testInsertOnCTypeWithoutPositionSpecifier(): void
    {
        // execute insert
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert, 'existing_element_key');

        // test expectations
        self::assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        self::assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
        self::assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][1][1]
        );
    }

    public function testInsertOnCTypeWithInvalidPositionSpecifier(): void
    {
        // execute insert
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert, 'somewhere:existing_element_key');

        // test expectations
        self::assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        self::assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][1][1]
        );
        self::assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
    }
}
