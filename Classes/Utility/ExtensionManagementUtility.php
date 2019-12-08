<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExtensionManagementUtility
 * @package Hoogi91\Spreadsheets\Utility
 */
class ExtensionManagementUtility extends \TYPO3\CMS\Core\Utility\ExtensionManagementUtility
{
    /**
     * @param array $itemArray Numerical array:
     *                          [0] => Plugin label
     *                          [1] => Underscored plugin name
     *                          [2] => Path to plugin icon relative to TYPO3_mainDir (optional use icon registry)
     * @param string $position before or after an underscored plugin name
     */
    public static function addItemToCTypeList(array $itemArray, string $position = ''): void
    {
        $columnConfig = &$GLOBALS['TCA']['tt_content']['columns'];
        if (is_array($columnConfig) && is_array($columnConfig['CType']['config']['items'])) {
            $items = &$columnConfig['CType']['config']['items'];

            foreach ($items as $k => $v) {
                // remove plugin if it was added before
                if ((string)$v[1] === (string)$itemArray[1]) {
                    unset($items[$k]);
                }
            }

            if (empty($position)) {
                $items[] = $itemArray;
                return;
            }

            [$insertPosition, $atField] = GeneralUtility::trimExplode(':', $position);
            if (empty($atField)) {
                $atField = $insertPosition;
                $insertPosition = 'before';
            } elseif (!in_array($insertPosition, ['before', 'after'])) {
                $items[] = $itemArray;
                return;
            }

            foreach ($items as $k => $v) {
                // add array before or after specific field
                if ((string)$atField === (string)$v[1]) {
                    if ($insertPosition === 'after') {
                        array_splice($items, ($k + 1), 0, [$itemArray]);
                    } elseif ($insertPosition === 'before') {
                        array_splice($items, $k, 0, [$itemArray]);
                    }
                }
            }
        }
    }
}
