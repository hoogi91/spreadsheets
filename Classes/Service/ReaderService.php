<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TYPO3\CMS\Core\Resource\FileReference;

class ReaderService
{
    final public const ALLOWED_EXTENSIONS = ['xls', 'xlsx', 'ods', 'xml', 'csv', 'html'];

    /**
     * @throws Reader\Exception
     */
    public function getSpreadsheet(FileReference|bool $reference): Spreadsheet
    {
        if (is_bool($reference) || $reference->getOriginalFile()->exists() === false) {
            throw new Reader\Exception('Reference original file doesn\'t exists!', 1_539_959_214);
        }

        return match ($reference->getExtension()) {
            'xls' => (new Reader\Xls())->load($reference->getForLocalProcessing()),
            'xlsx' => (new Reader\Xlsx())->load($reference->getForLocalProcessing()),
            'ods' => (new Reader\Ods())->load($reference->getForLocalProcessing()),
            'xml' => (new Reader\Xml())->load($reference->getForLocalProcessing()),
            'csv' => (new Reader\Csv())->load($reference->getForLocalProcessing()),
            'html' => (new Reader\Html())->load($reference->getForLocalProcessing()),
            default => throw new Reader\Exception(
                sprintf(
                    'Reference has not allowed file extension "%s"! Allowed Extensions are "%s"',
                    $reference->getExtension(),
                    implode(',', self::ALLOWED_EXTENSIONS)
                ),
                1_514_909_945
            ),
        };
    }
}
