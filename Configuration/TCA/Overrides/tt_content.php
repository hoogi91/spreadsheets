<?php
defined('TYPO3_MODE') or die();

call_user_func(function ($extKey, $table) {
    // Adds the content element to the "Type" dropdown
    \Hoogi91\Spreadsheets\Utility\ExtensionManagementUtility::addItemToCTypeList([
        'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:wizards.spreadsheets_table.title',
        'spreadsheets_table',
        'mimetypes-open-document-spreadsheet',
    ], 'after:table');

    // add own assets upload field
    $allowedFileExtensions = \Hoogi91\Spreadsheets\Service\ReaderService::ALLOWED_EXTENSTIONS;
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'tx_spreadsheets_assets'        => [
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'tx_spreadsheets_assets',
                [
                    'appearance'       => [
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
                implode(',', $allowedFileExtensions)
            ),
        ],
        'tx_spreadsheets_ignore_styles' => [
            'config' => [
                'type'    => 'check',
                'items'   => [
                    [
                        'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tca.tx_spreadsheets_ignore_styles.label',
                        '',
                    ],
                ],
                'default' => false,
            ],
        ],
    ]);


    // add own palettes
    $GLOBALS['TCA'][$table]['palettes']['tableSpreadsheatLayout'] = [
        'showitem' => 'tx_spreadsheets_ignore_styles;LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tca.tx_spreadsheets_ignore_styles, table_class',
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
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.table_layout;tableSpreadsheatLayout,
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
            'bodytext'    => [
                'config' => [
                    'renderType'  => 'spreadsheetInput',
                    'uploadField' => 'tx_spreadsheets_assets',
                    'sheetsOnly'  => true,
                    'size'        => 50,
                ],
            ],
            'table_class' => [
                'displayCond' => 'FIELD:tx_spreadsheets_ignore_styles:>:0',
            ],
        ],
    ];
}, 'spreadsheets', 'tt_content');
