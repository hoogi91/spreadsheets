<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\ValueMappingService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Class ValueMappingServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class ValueMappingServiceTest extends UnitTestCase
{
    /**
     * @var ValueMappingService
     */
    private $valueMappingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->valueMappingService = new ValueMappingService();
    }

    public function valueMappingDataProvider(): array
    {
        return [
            ['unknown-map', 'value', ''],
            ['border-style', 'unknown-value', ''],
            ['border-style', 'unknown-value-with-fallback', 'my-fallback-value', 'my-fallback-value'],
            ['border-style', Border::BORDER_NONE, 'none'],
            ['border-style', Border::BORDER_MEDIUMDASHDOT, '2px dashed'],
            ['halign', Alignment::HORIZONTAL_LEFT, 'left'],
            ['halign', Alignment::HORIZONTAL_JUSTIFY, 'justify'],
            ['halign-backend', Alignment::HORIZONTAL_LEFT, null],
            ['halign-backend', Alignment::HORIZONTAL_JUSTIFY, 'j'],
            ['valign', Alignment::VERTICAL_BOTTOM, 'bottom'],
            ['valign', Alignment::VERTICAL_JUSTIFY, 'middle'],
            ['valign-backend', Alignment::VERTICAL_BOTTOM, null],
            ['valign-backend', Alignment::VERTICAL_JUSTIFY, 'm'],
        ];
    }

    /**
     * @param string $map
     * @param string $value
     * @param string|null $expected
     * @param string|null $default
     *
     * @dataProvider valueMappingDataProvider
     */
    public function testValueMapping(string $map, string $value, ?string $expected, ?string $default = null): void
    {
        self::assertEquals($expected, $this->valueMappingService->convertValue($map, $value, $default));
    }
}
