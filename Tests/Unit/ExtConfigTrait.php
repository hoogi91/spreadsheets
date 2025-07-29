<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit;

use PHPUnit\Framework\MockObject\Generator\Generator;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

trait ExtConfigTrait
{
    /**
     * @return ExtensionConfiguration&MockObject
     */
    private static function getExtensionConfig(bool $tabsEnabled = false, bool $readEmptyCells = false): MockObject
    {
        $mock = method_exists(Generator::class, 'testDouble')
            ? (new Generator())->testDouble(ExtensionConfiguration::class, true)
            : (new Generator())->getMock(ExtensionConfiguration::class);
        $mock->method('get')->willReturnMap(
            [
                ['ce_tabs', $tabsEnabled],
                ['read_empty_cells', $readEmptyCells],
            ]
        );

        return $mock;
    }
}
