<?php
defined('TYPO3_MODE') or die();

(static function ($extKey) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $extKey,
        'Configuration/TypoScript/',
        'Spreadsheets'
    );
})('spreadsheets');
