<?php
defined('TYPO3_MODE') or die();

(function ($extKey) {
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet') === false) {
        /** @noinspection PhpIncludeInspection */
        include(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
            $extKey,
            'Resources/Private/Composer/vendor/autoload.php'
        ));
    }

    if (TYPO3_MODE === 'BE') {
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
            'class'    => \Hoogi91\Spreadsheets\Form\Element\DataInputElement::class,
        ];

        // add handsontable dependency to backend requireJS config (for above form node type)
        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->addRequireJsConfiguration([
            'paths' => [
                'jquery'       => 'sysext/core/Resources/Public/JavaScript/Contrib/jquery/jquery',
                'Handsontable' => '../typo3conf/ext/spreadsheets/Resources/Public/JavaScript/HandsOnTable/handsontable.full.min',
            ],
            'shim'  => [
                'Handsontable' => [
                    'deps'    => ['jquery'],
                    'exports' => 'Handsontable',
                ],
            ],
        ]);
    }
})('spreadsheets');
