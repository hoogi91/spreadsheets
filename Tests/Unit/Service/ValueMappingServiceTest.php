<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\ValueMappingService;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ValueMappingServiceTest extends UnitTestCase
{
    private ValueMappingService $valueMappingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->valueMappingService = new ValueMappingService();
    }

    /**
     * @return array<int, array<int, string|null>>
     */
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
     * @dataProvider valueMappingDataProvider
     */
    public function testValueMapping(string $map, string $value, ?string $expected, ?string $default = null): void
    {
        self::assertEquals($expected, $this->valueMappingService->convertValue($map, $value, $default));
    }
}
