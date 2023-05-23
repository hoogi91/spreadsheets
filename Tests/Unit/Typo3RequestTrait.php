<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Unit;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

trait Typo3RequestTrait
{
    public function setTypo3Request(): void
    {
        $locale = 'en_US.UTF-8';
        if ((new Typo3Version())->getMajorVersion() > 11) {
            $locale = $this->createConfiguredMock(Locale::class, ['getLanguageCode' => $locale]);
        }

        // mock backend request mode
        $GLOBALS['TYPO3_REQUEST'] = $this->createMock(ServerRequestInterface::class);
        $GLOBALS['TYPO3_REQUEST']->method('getAttribute')->willReturnMap(
            [
                ['language', null, $this->createConfiguredMock(SiteLanguage::class, ['getLocale' => $locale])],
                ['applicationType', null, SystemEnvironmentBuilder::REQUESTTYPE_BE],
            ]
        );
    }
}
