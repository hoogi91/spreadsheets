<?php

namespace Hoogi91\Spreadsheets\Tests\Unit;

/**
 * Trait TsfeSetupTrait
 * @package Hoogi91\Spreadsheets\Tests\Unit
 */
trait TsfeSetupTrait
{

    public static function setupDefaultTSFE(): void
    {
        $GLOBALS['TSFE'] = $GLOBALS['TSFE'] ?? new \stdClass();
        $GLOBALS['TSFE']->config['config']['locale_all'] = 'de';
    }
}
