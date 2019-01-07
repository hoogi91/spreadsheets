<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\DataProcessing;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Page\PageGenerator;
use Hoogi91\Spreadsheets\Service\ExtractorService;

/**
 * Class SpreadsheetProcessor
 * @package Hoogi91\Spreadsheets\DataProcessing
 */
class SpreadsheetProcessor implements DataProcessorInterface
{

    /**
     * @param ContentObjectRenderer $cObj                       The content object renderer,
     *                                                          which contains data of the content element
     * @param array                 $contentObjectConfiguration The configuration of Content Object
     * @param array                 $processorConfiguration     The configuration of this processor
     * @param array                 $processedData              Key/value store of processed data
     *                                                          (e.g. to be passed to a Fluid View)
     *
     * @return array the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $databaseValue = $cObj->stdWrapValue('value', $processorConfiguration, '');
        if (empty($databaseValue)) {
            return $processedData;
        }

        $extractorService = $this->getExtractorService($databaseValue);
        if (!$extractorService instanceof ExtractorService) {
            return $processedData;
        }

        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'spreadsheet');
        $processedData[$targetVariableName] = [
            'sheetIndex' => $extractorService->getSheetIndex(),
            'headData'   => $extractorService->getHeadData(),
            'bodyData'   => $extractorService->getBodyData(),
        ];

        $ignoreStyles = (bool)$cObj->stdWrapValue('ignoreStyles', $processorConfiguration['options.'] ?: [], false);
        if (!$ignoreStyles) {
            $htmlIdentifier = $cObj->stdWrapValue('htmlIdentifier', $processorConfiguration['options.'] ?: [], 'sheet');
            $this->addStyleSheetContentToPageRenderer($extractorService->getStyles($htmlIdentifier));
        }

        return $processedData;
    }

    /**
     * @param string $content
     */
    protected function addStyleSheetContentToPageRenderer($content)
    {
        if (version_compare(TYPO3_version, '9.4', '>=')) {
            $tempFile = GeneralUtility::writeStyleSheetContentToTemporaryFile($content);
        } else {
            $tempFile = PageGenerator::inline2TempFile($content, 'css');
        }
        $this->getPageRenderer()->addCssFile($tempFile);
    }

    /**
     * @param string $databaseValue
     *
     * @return ExtractorService
     */
    protected function getExtractorService($databaseValue)
    {
        return ExtractorService::createFromDatabaseString($databaseValue);
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
