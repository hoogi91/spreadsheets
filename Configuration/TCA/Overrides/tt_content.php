<?php
defined('TYPO3') or die();

(static function (string $extKey, string $table, bool $isTabsFeatureEnabled = false) {
    // Adds the content element to the "Type" dropdown
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:wizards.spreadsheets_table.title',
            'spreadsheets_table',
            'mimetypes-open-document-spreadsheet',
        ],
        'table',
        'after'
    );

    if ($isTabsFeatureEnabled === true) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'CType',
            [
                'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:wizards.spreadsheets_tabs.title',
                'spreadsheets_tabs',
                'mimetypes-open-document-database',
            ],
            'spreadsheets_table',
            'after'
        );
    }

    // add own assets upload field
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'tx_spreadsheets_assets' => [
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'tx_spreadsheets_assets',
                [
                    'foreign_table' => 'sys_file_reference',
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.asset_references.addFileReference',
                    ],
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '--palette--;;filePalette',
                            ],
                        ],
                    ],
                ],
                implode(',', \Hoogi91\Spreadsheets\Service\ReaderService::ALLOWED_EXTENSIONS)
            ),
        ],
        'tx_spreadsheets_ignore_styles' => [
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tca.tx_spreadsheets_ignore_styles.label',
                        '',
                    ],
                ],
                'default' => 0,
            ],
        ],
    ]);


    // add own palettes
    $GLOBALS['TCA'][$table]['palettes']['tableSpreadsheetLayout'] = [
        'showitem' => 'tx_spreadsheets_ignore_styles;LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tca.tx_spreadsheets_ignore_styles, table_class, table_header_position, table_tfoot',
    ];

    // Configure the default backend fields for the content element
    $GLOBALS['TCA'][$table]['types']['spreadsheets_table'] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.headers;headers,
                tx_spreadsheets_assets;LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tca.assets,
                bodytext;LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tca.bodytext,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.table_layout;tableSpreadsheetLayout,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                categories,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                rowDescription,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ',

        'columnsOverrides' => [
            'bodytext' => [
                'displayCond' => 'FIELD:CType:=:spreadsheets_table',
                'config' => [
                    'renderType' => 'spreadsheetInput',
                    'uploadField' => 'tx_spreadsheets_assets',
                    'sheetsOnly' => true,
                    'size' => 100,
                ],
            ],
            'table_class' => [
                'displayCond' => 'FIELD:tx_spreadsheets_ignore_styles:>:0',
            ],
            'table_header_position' => [
                'displayCond' => 'FIELD:CType:=:spreadsheets_table',
            ],
            'table_tfoot' => [
                'displayCond' => 'FIELD:CType:=:spreadsheets_table',
            ],
        ],
    ];

    if ($isTabsFeatureEnabled === true) {
        // use same settings for tabs
        $GLOBALS['TCA'][$table]['types']['spreadsheets_tabs'] = $GLOBALS['TCA'][$table]['types']['spreadsheets_table'];
        $GLOBALS['TCA'][$table]['types']['spreadsheets_tabs']['columnsOverrides']['tx_spreadsheets_assets']['config']['maxitems'] = 1;
    }
})(
    'spreadsheets',
    'tt_content',
    TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Configuration\Features::class)
        ->isFeatureEnabled('spreadsheets.tabsContentElement')
);
