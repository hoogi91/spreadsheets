<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\DataProcessing;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use Hoogi91\Spreadsheets\Service\ExtractorService;
use Hoogi91\Spreadsheets\Service\StyleService;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Class SpreadsheetProcessor
 * @package Hoogi91\Spreadsheets\DataProcessing
 */
class SpreadsheetProcessor implements DataProcessorInterface
{

    /**
     * @var ExtractorService
     */
    private $extractorService;

    /**
     * @var StyleService
     */
    private $styleService;

    /**
     * @var PageRenderer
     */
    private $pageRenderer;

    /**
     * SpreadsheetProcessor constructor.
     * @param ExtractorService $extractorService
     * @param StyleService $styleService
     * @param PageRenderer $pageRenderer
     */
    public function __construct(
        ExtractorService $extractorService,
        StyleService $styleService,
        PageRenderer $pageRenderer
    ) {
        $this->extractorService = $extractorService;
        $this->styleService = $styleService;
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * @param ContentObjectRenderer $cObj The content object renderer,
     *                                                          which contains data of the content element
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data
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
        $value = $cObj->stdWrapValue('value', $processorConfiguration, '');
        if (empty($value)) {
            return $processedData;
        }

        try {
            // get spreadsheet DSN value from content object to parse and render
            $dsnValue = DsnValueObject::createFromDSN($value);
            $extraction = $this->extractorService->getDataByDsnValueObject($dsnValue, true);
        } catch (InvalidDataSourceNameException | ResourceDoesNotExistException | ReaderException $exception) {
            // if DSN could not be parsed or is invalid the output is empty
            // or the extraction failed
            return $processedData;
        }

        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'spreadsheet');
        $processedData[$targetVariableName] = [
            'sheetIndex' => $dsnValue->getSheetIndex(),
            'headData' => $extraction->getHeadData(),
            'bodyData' => $extraction->getBodyData(),
        ];

        $ignoreStyles = (bool)$cObj->stdWrapValue('ignoreStyles', $processorConfiguration['options.'] ?: []);
        if ($ignoreStyles !== false) {
            return $processedData;
        }

        $htmlIdentifier = $cObj->stdWrapValue('htmlIdentifier', $processorConfiguration['options.'] ?: [], 'sheet');
        $this->pageRenderer->addCssFile(
            GeneralUtility::writeStyleSheetContentToTemporaryFile(
                $this->styleService->getStylesheet($extraction->getSpreadsheet())->toCSS($htmlIdentifier)
            )
        );

        return $processedData;
    }
}
