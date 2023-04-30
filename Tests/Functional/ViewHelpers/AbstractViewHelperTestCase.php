<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Tests\Functional\ViewHelpers;

use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

abstract class AbstractViewHelperTestCase extends FunctionalTestCase
{
    /**
     * @var array<string, non-empty-string>
     */
    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/spreadsheets/Tests/Fixtures/' => 'fileadmin/user_upload',
    ];

    /**
     * @var array<non-empty-string>
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/spreadsheets',
    ];

    /**
     * @param array<mixed> $arguments
     */
    protected function getView(string $template, array $arguments = []): TemplateView
    {
        $view = new TemplateView();
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($template);
        $view->getRenderingContext()->setViewHelperResolver(
            new ViewHelperResolver(
                $this->getContainer(),
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces'] ?? []
            )
        );
        $view->getRenderingContext()->getViewHelperResolver()->addNamespace(
            'test',
            'Hoogi91\\Spreadsheets\\ViewHelpers'
        );
        $view->assignMultiple($arguments);

        return $view;
    }
}
