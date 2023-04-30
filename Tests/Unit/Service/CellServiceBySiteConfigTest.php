<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\CellService;
use Hoogi91\Spreadsheets\Service\StyleService;
use Hoogi91\Spreadsheets\Service\ValueMappingService;
use stdClass;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

class CellServiceBySiteConfigTest extends CellServiceTest
{
    public function setUp(): void
    {
        parent::setUp();

        // unset globals TSFE and define site finder object from which to get the correct language
        unset($GLOBALS['TSFE']);
        $GLOBALS['TSFE'] = new stdClass();
        $GLOBALS['TSFE']->id = 1;

        $locale = 'de';
        if ((new Typo3Version())->getMajorVersion() > 11) {
            $locale = $this->createMock(Locale::class);
            $locale->expects(self::once())->method('getLanguageCode')->willReturn('de');
        }

        // mock site finder to get language
        $siteLanguage = $this->createMock(SiteLanguage::class);
        $siteLanguage->expects(self::once())->method('getLocale')->willReturn($locale);
        $site = $this->createMock(Site::class);
        $site->expects(self::once())->method('getLanguageById')->with(123)->willReturn($siteLanguage);
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->expects(self::once())->method('getSiteByPageId')->with(1)->willReturn($site);

        // mock context to get correct language id
        $context = $this->createMock(Context::class);
        $context->method('getAspect')->with('language')->willReturn(
            $this->createConfiguredMock(LanguageAspect::class, ['getId' => 123])
        );

        $mappingService = $this->createTestProxy(ValueMappingService::class);
        $styleService = $this->createTestProxy(StyleService::class, [$mappingService]);
        $this->cellService = new CellService($styleService, $siteFinder, $context);
    }
}
