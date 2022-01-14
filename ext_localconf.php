<?php

defined('TYPO3') or die();

(static function (string $extKey, array $extConf) {
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet') === false) {
        /** @noinspection PhpIncludeInspection */
        include(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
            $extKey,
            'Resources/Private/Composer/vendor/autoload.php'
        ));
    }

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['spreadsheets.tabsContentElement'] =
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['spreadsheets.tabsContentElement'] ?? ($extConf['ce_tabs'] === '1');

    // add content element to insert tables in content element wizard
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $extKey . '/Configuration/PageTSconfig/NewContentElementWizard.typoscript">'
    );

    // register template for backend preview rendering
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $extKey . '/Configuration/PageTSconfig/BackendPreview.typoscript">'
    );

    // add field type to form engine
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1513268927167] = [
        'nodeName' => 'spreadsheetInput',
        'priority' => 30,
        'class' => \Hoogi91\Spreadsheets\Form\Element\DataInputElement::class,
    ];

    // register data handler hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Hoogi91\Spreadsheets\Hooks\DataHandlerHook::class;
})(
    'spreadsheets',
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['spreadsheets'] ?? unserialize(
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['spreadsheets'],
        ['allowed_classes' => false]
    )
);
