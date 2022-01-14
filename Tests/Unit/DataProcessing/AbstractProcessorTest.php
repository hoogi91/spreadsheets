<?php

namespace Hoogi91\Spreadsheets\Tests\Unit\DataProcessing;

use Hoogi91\Spreadsheets\DataProcessing\AbstractProcessor;
use Hoogi91\Spreadsheets\Service;
use Hoogi91\Spreadsheets\Tests\Unit\ArrayAssertTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer as CObjRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class AbstractProcessorTest
 * @package Hoogi91\Spreadsheets\Tests\Unit\DataProcessing
 */
abstract class AbstractProcessorTest extends UnitTestCase
{

    use ArrayAssertTrait;

    protected const INPUT_DATA = [
        'someVar' => 'data',
    ];

    /**
     * @var CObjRenderer|MockObject
     */
    protected $cObjRendererMock;

    /**
     * @var PageRenderer|MockObject
     */
    protected $pageRendererMock;

    /**
     * @var Service\ReaderService|MockObject
     */
    protected $readerService;

    /**
     * @var Service\ExtractorService|MockObject
     */
    protected $extractorService;

    /**
     * @var Service\StyleService|MockObject
     */
    protected $styleService;

    /**
     * @var FileRepository|MockObject
     */
    protected $fileRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // add general TYPO3 mock objects
        $this->pageRendererMock = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->getMock();
        $this->cObjRendererMock = $this->getMockBuilder(CObjRenderer::class)->disableOriginalConstructor()->getMock();
        $this->cObjRendererMock->method('stdWrapValue')->willReturnCallback(
            static function ($key, array $config, $defaultValue = '') {
                return $config[$key] ?? $defaultValue;
            }
        );
        $this->readerService = $this->createMock(Service\ReaderService::class);
        $this->extractorService = $this->createMock(Service\ExtractorService::class);
        $this->styleService = $this->createMock(Service\StyleService::class);
        $this->fileRepository = $this->createMock(FileRepository::class);
    }

    abstract protected function getDataProcessor(): AbstractProcessor;

    abstract protected function validInputExpectations(MockObject $spreadsheetMock): void;

    abstract protected function invalidInputExpectations(): void;

    abstract public function processingDataProvider(): array;

    /**
     * @param array $processConfig
     * @param array $expectedResult
     *
     * @dataProvider processingDataProvider
     */
    public function testProcessing(array $processConfig, array $expectedResult): void
    {
        // add page renderer expectation based on ignoreStyle option
        if (isset($processConfig['options.']['ignoreStyles']) && $processConfig['options.']['ignoreStyles'] === 1) {
            $this->pageRendererMock->expects(self::never())->method('addCssFile');
            $this->pageRendererMock->expects(self::never())->method('addCssInlineBlock');
        } elseif (static::INPUT_DATA !== $expectedResult) {
            $this->pageRendererMock->expects(self::once())->method('addCssFile')->with($this->isType('string'));
            $this->pageRendererMock->expects(self::once())->method('addCssInlineBlock')->with(
                AbstractProcessor::class,
                $processConfig['options.']['additionalStyles']
            );
        }

        if (static::INPUT_DATA !== $expectedResult) {
            $referenceMock = $this->createMock(FileReference::class);
            $this->fileRepository->expects(self::once())
                ->method('findFileReferenceByUid')
                ->willReturn($referenceMock);

            $spreadsheetMock = $this->createMock(Spreadsheet::class);
            $this->readerService->expects(self::once())
                ->method('getSpreadsheet')
                ->with($referenceMock)
                ->willReturn($spreadsheetMock);

            $this->validInputExpectations($spreadsheetMock);
        } else {
            $this->fileRepository->expects(self::never())->method('findFileReferenceByUid');
            $this->readerService->expects(self::never())->method('getSpreadsheet');
            $this->invalidInputExpectations();
        }

        // execute processor
        $result = $this->getDataProcessor()->process(
            $this->cObjRendererMock,
            [],
            $processConfig,
            static::INPUT_DATA
        );
        self::assertEquals($expectedResult, $result);
    }
}
