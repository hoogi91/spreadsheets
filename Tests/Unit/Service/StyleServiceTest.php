<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service;
use Hoogi91\Spreadsheets\Service\StyleService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StyleServiceTest extends UnitTestCase
{
    private StyleService $styleService;

    private Spreadsheet $spreadsheet;

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
        $value = $this->spreadsheet->getActiveSheet()->getCell('D5')->getValue();
        self::assertInstanceOf(RichText::class, $value);
        self::assertInstanceOf(Run::class, $value->getRichTextElements()[0]);

        self::assertSame(
            'color:#000000',
            $this->styleService->getStylesheetForRichTextElement($value->getRichTextElements()[0])->toInlineCSS()
        );

        // update font and see if it has been cleared
        $value->getRichTextElements()[0]->setFont(null);
        self::assertSame(
            '',
            $this->styleService->getStylesheetForRichTextElement($value->getRichTextElements()[0])->toInlineCSS()
        );
    }
}
