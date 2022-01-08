<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class StyleServiceTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class StyleServiceTest extends UnitTestCase
{

    /**
     * @var Service\StyleService
     */
    private $styleService;

    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    protected function setUp(): void
    {
        parent::setUp();

        // setup reader mock instance
        $this->spreadsheet = (new Xlsx())->load(dirname(__DIR__, 2) . '/Fixtures/01_fixture.xlsx');
        $this->styleService = new Service\StyleService(new Service\ValueMappingService());
    }

    public function testGettingStylesheetForSpreadsheet(): void
    {
        self::assertStringEqualsFile(
            dirname(__DIR__, 2) . '/Fixtures/01_fixture.css',
            $this->styleService->getStylesheet($this->spreadsheet)->toCSS()
        );
    }

    public function testGettingStylesheetForRichTextElement(): void
    {
        /** @var RichText|mixed $value */
        $value = $this->spreadsheet->getActiveSheet()->getCell('D5')->getValue();
        self::assertInstanceOf(RichText::class, $value);
        self::assertInstanceOf(Run::class, $value->getRichTextElements()[0]);

        self::assertSame(
            'color:#000000',
            $this->styleService->getStylesheetForRichTextElement($value->getRichTextElements()[0])->toInlineCSS()
        );
    }
}
