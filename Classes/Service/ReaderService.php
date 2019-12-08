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

        if (in_array($reference->getExtension(), static::ALLOWED_EXTENSIONS, true) === false) {
            throw new Reader\Exception(sprintf(
                'Reference has not allowed file extension "%s"! Allowed Extensions are "%s"',
                $reference->getExtension(),
                implode(',', static::ALLOWED_EXTENSIONS)
            ), 1514909945);
        }

        switch ($reference->getExtension()) {
            case 'xls':
                return (new Reader\Xls())->load($reference->getForLocalProcessing());
            case 'xlsx':
                return (new Reader\Xlsx())->load($reference->getForLocalProcessing());
            case 'ods':
                return (new Reader\Ods())->load($reference->getForLocalProcessing());
            case 'xml':
                return (new Reader\Xml())->load($reference->getForLocalProcessing());
            case 'csv':
                return (new Reader\Csv())->load($reference->getForLocalProcessing());
            case 'html':
                return (new Reader\Html())->load($reference->getForLocalProcessing());
        }

        throw new Reader\Exception(sprintf(
            'Unknown file extension "%s" could not be loaded',
            $reference->getExtension()
        ), 1514909946);
    }
}
