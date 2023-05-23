<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility as ExtUtil;

class ValueMappingService
{
    /**
     * @var array<string, array<string, string|null>>
     */
    private array $mappings;

    public function __construct()
    {
        $configFilePath = ExtUtil::extPath('spreadsheets') . 'Configuration/ValueMappings.php';
        $this->mappings = include $configFilePath;
    }

    public function convertValue(string $map, ?string $value, ?string $default = null): ?string
    {
        return $this->mappings[$map][$value] ?? $default;
    }
}
