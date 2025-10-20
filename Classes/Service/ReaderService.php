<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\FileReference;

class ReaderService
{
    final public const ALLOWED_EXTENSIONS = ['xls', 'xlsx', 'ods', 'xml', 'csv', 'html'];

    public function __construct(private readonly ExtensionConfiguration $extensionConfiguration)
    {
    }

    /**
     * @throws Reader\Exception
     */
    public function getSpreadsheet(FileReference|bool $reference): Spreadsheet
    {
        if (is_bool($reference) || $reference->getOriginalFile()->exists() === false) {
            throw new Reader\Exception('Reference original file doesn\'t exists!', 1_539_959_214);
        }

        $shouldReadEmptyCells = $this->extensionConfiguration->get('spreadsheets', 'read_empty_cells') === '1';

        return match ($reference->getExtension()) {
            'xls' => (new Reader\Xls())->setReadEmptyCells($shouldReadEmptyCells)->load(
                $reference->getForLocalProcessing(false)
            ),
            'xlsx' => (new Reader\Xlsx())->setReadEmptyCells($shouldReadEmptyCells)->load(
                $reference->getForLocalProcessing(false)
            ),
            'ods' => (new Reader\Ods())->load($reference->getForLocalProcessing(false)),
            'xml' => (new Reader\Xml())->load($reference->getForLocalProcessing(false)),
            'csv' => (new Reader\Csv())->load($reference->getForLocalProcessing(false)),
            'html' => (new Reader\Html())->load($reference->getForLocalProcessing(false)),
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
