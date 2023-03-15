<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit;

use stdClass;

trait TsfeSetupTrait
{
    public static function setupDefaultTSFE(): void
    {
        $GLOBALS['TSFE'] ??= new stdClass();
        $GLOBALS['TSFE']->config['config']['locale_all'] = 'de';
    }
}
