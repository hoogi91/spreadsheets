<?php

namespace Hoogi91\Spreadsheets\Tests\Utility;

use Hoogi91\Spreadsheets\Utility\ExtensionManagementUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ExtensionManagementUtilityTest
 * @package Hoogi91\Spreadsheets\Tests\Utility
 */
class ExtensionManagementUtilityTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected static $elementToInsert = [
        'Title of Plugin/Element',
        'element_key',
        'icon-identifier-of-element',
    ];

    /**
     * initialize with typical TCA structure of tt_content's CType column
     */
    public function setUp()
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

    /**
     * @test
     */
    public function testInsertOnCType()
    {
        // execute insert
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert);

        // test expectations
        $this->assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        $this->assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][1][1]
        );
        $this->assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
    }

    /**
     * @test
     */
    public function testInsertOnCTypeBeforeSpecificElement()
    {
        // execute insert
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert, 'before:existing_element_key');

        // test expectations
        $this->assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        $this->assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
        $this->assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][1][1]
        );
    }

    /**
     * @test
     */
    public function testInsertOnCTypeAfterSpecificElement()
    {
        // execute insert
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert, 'after:existing_element_key');

        // test expectations
        $this->assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        $this->assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][1][1]
        );
        $this->assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
    }

    /**
     * @test
     */
    public function testInsertOnCTypeWithoutPositionSpecifier()
    {
        // execute insert
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert, 'existing_element_key');

        // test expectations
        $this->assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        $this->assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
        $this->assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][1][1]
        );
    }

    /**
     * @test
     */
    public function testInsertOnCTypeWithInvalidPositionSpecifier()
    {
        // execute insert
        ExtensionManagementUtility::addItemToCTypeList(self::$elementToInsert, 'somewhere:existing_element_key');

        // test expectations
        $this->assertCount(2, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        $this->assertEquals(
            'element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][1][1]
        );
        $this->assertEquals(
            'existing_element_key',
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][0][1]
        );
    }
}
