<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\Service;

use Hoogi91\Spreadsheets\Service\CellService;
use Hoogi91\Spreadsheets\Service\StyleService;
use Hoogi91\Spreadsheets\Service\ValueMappingService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CellServiceBySiteConfigTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\Service
 */
class CellServiceBySiteConfigTest extends CellServiceTest
{

    public function setUp(): void
    {
        parent::setUp();

        // unset globals TSFE and define site finder object from which to get the correct language
        unset($GLOBALS['TSFE']);
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->id = 1;

        // mock site finder to get language
        $siteLanguage = $this->createMock(SiteLanguage::class);
        $siteLanguage->expects(self::once())->method('getLocale')->willReturn('de');
        $site = $this->createMock(Site::class);
        $site->expects(self::once())->method('getLanguageById')->with(123)->willReturn($siteLanguage);
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->expects(self::once())->method('getSiteByPageId')->with(1)->willReturn($site);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinder);

        // mock context to get correct language id
        $context = $this->createMock(Context::class);
        $context->method('getPropertyFromAspect')->with('language', 'id')->willReturn(123);
        GeneralUtility::setSingletonInstance(Context::class, $context);

        $this->cellService = new CellService(new StyleService(new ValueMappingService()));
    }
}
