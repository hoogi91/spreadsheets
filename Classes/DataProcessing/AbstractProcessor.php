<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\DataProcessing;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use Hoogi91\Spreadsheets\Exception\InvalidDataSourceNameException;
use Hoogi91\Spreadsheets\Service\ExtractorService;
use Hoogi91\Spreadsheets\Service\ReaderService;
use Hoogi91\Spreadsheets\Service\StyleService;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

abstract class AbstractProcessor implements DataProcessorInterface
{
    public function __construct(
        private readonly ReaderService $readerService,
        private readonly ExtractorService $extractorService,
        private readonly StyleService $styleService,
        private readonly FileRepository $fileRepository,
        private readonly PageRenderer $pageRenderer
    ) {
    }

    public function getExtractorService(): ExtractorService
    {
        return $this->extractorService;
    }

    /**
     * @param ContentObjectRenderer $cObj The content object renderer, which contains data of the content element
     * @param array<mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<string, array<mixed>> $processorConfiguration The configuration of this processor
     * @param array<mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     *
     * @return array<mixed> the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $value = (string)$cObj->stdWrapValue('value', $processorConfiguration, '');
        if (empty($value)) {
            return $processedData;
        }

        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'spreadsheets');

        try {
            // get spreadsheet DSN value from content object to parse and render
            $dsnValue = DsnValueObject::createFromDSN($value);
            $spreadsheet = $this->readerService->getSpreadsheet(
                $this->fileRepository->findFileReferenceByUid($dsnValue->getFileReference())
            );

            $processedData[$targetVariableName] = $this->getTemplateData($dsnValue, $spreadsheet, $processedData);
        } catch (InvalidDataSourceNameException | ResourceDoesNotExistException | ReaderException) {
            // if DSN could not be parsed or is invalid the output is empty
            // or the extraction failed
            return $processedData;
        }

        $ignoreStyles = (bool)$cObj->stdWrapValue('ignoreStyles', $processorConfiguration['options.'] ?? []);
        if ($ignoreStyles !== false) {
            return $processedData;
        }

        $additionalStyles = (string)$cObj->stdWrapValue('additionalStyles', $processorConfiguration['options.'] ?? []);
        if (empty($additionalStyles) === false) {
            $this->pageRenderer->addCssInlineBlock(self::class, $additionalStyles);
        }

        $htmlIdentifier = (string)$cObj->stdWrapValue(
            'htmlIdentifier',
            $processorConfiguration['options.'] ?? [],
            'sheet'
        );
        $this->pageRenderer->addCssFile(
            GeneralUtility::writeStyleSheetContentToTemporaryFile(
                $this->styleService->getStylesheet($spreadsheet)->toCSS($htmlIdentifier)
            )
        );

        return $processedData;
    }

    /**
     * @param array<mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     *
     * @return array<mixed>
     */
    abstract protected function getTemplateData(
        DsnValueObject $dsn,
        Spreadsheet $spreadsheet,
        array $processedData
    ): array;
}
