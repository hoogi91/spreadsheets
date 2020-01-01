<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use Hoogi91\Spreadsheets\Utility\ExtensionManagementUtility as ExtUtil;

/**
 * Class ValueMappingService
 * @package Hoogi91\Spreadsheets\Service
 */
class ValueMappingService
{
    /**
     * @var array
     */
    private $mappings;

    /**
     * ValueMappingService constructor.
     */
    public function __construct()
    {
        $configFilePath = ExtUtil::extPath('spreadsheets') . 'Configuration/ValueMappings.php';
        /** @noinspection PhpIncludeInspection */
        $this->mappings = include $configFilePath;
    }

    /**
     * Get mapped/converted value from map by current value or return default
     *
     * @param string $map
     * @param string $value
     * @param string|null $default
     *
     * @return string|null
     */
    public function convertValue(string $map, string $value, ?string $default = null): ?string
    {
        return $this->mappings[$map][$value] ?? $default;
    }
}
