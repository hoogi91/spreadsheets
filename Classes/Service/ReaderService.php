<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * Class ReaderService
 * @package Hoogi91\Spreadsheets\Service
 */
class ReaderService
{

    public const ALLOWED_EXTENSIONS = ['xls', 'xlsx', 'ods', 'xml', 'csv', 'html'];

    /**
     * @param FileReference $reference
     *
     * @return Spreadsheet
     * @throws Reader\Exception
     */
    public function getSpreadsheet(FileReference $reference): Spreadsheet
    {
        if ($reference->getOriginalFile()->exists() === false) {
            throw new Reader\Exception('Reference original file doesn\'t exists!', 1539959214);
        }

        $extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        );
        $disableReadingEmptyCells = $extConf->get('spreadsheets', 'disable_reading_empty_cells') === '1';

        switch ($reference->getExtension()) {
            case 'xls':
                $reader = $disableReadingEmptyCells
                    ? (new Reader\Xls())->setReadEmptyCells(false)
                    : new Reader\Xls();

                return $reader->load($reference->getForLocalProcessing());
            case 'xlsx':
                $reader = $disableReadingEmptyCells
                    ? (new Reader\Xlsx())->setReadEmptyCells(false)
                    : new Reader\Xlsx();

                return $reader->load($reference->getForLocalProcessing());
            case 'ods':
                return (new Reader\Ods())->load($reference->getForLocalProcessing());
            case 'xml':
                return (new Reader\Xml())->load($reference->getForLocalProcessing());
            case 'csv':
                return (new Reader\Csv())->load($reference->getForLocalProcessing());
            case 'html':
                return (new Reader\Html())->load($reference->getForLocalProcessing());
        }

        throw new Reader\Exception(
            sprintf(
                'Reference has not allowed file extension "%s"! Allowed Extensions are "%s"',
                $reference->getExtension(),
                implode(',', static::ALLOWED_EXTENSIONS)
            ),
            1514909945
        );
    }
}
