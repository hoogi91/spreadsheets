<?php
declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Service;

use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Html as HtmlReader;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Reader\Ods as OdsReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xml as XmlReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use TYPO3\CMS\Core\Resource\FileReference;
use Hoogi91\Spreadsheets\Traits\SheetIndexTrait;

/**
 * Class ReaderService
 * @package Hoogi91\Spreadsheets\Service
 */
class ReaderService
{
    use SheetIndexTrait;

    const ALLOWED_EXTENSTIONS = ['xls', 'xlsx', 'ods', 'xml', 'csv', 'html'];

    /**
     * @var IReader
     */
    protected $readerInstance = null;

    /**
     * ReaderService constructor.
     *
     * @param FileReference $reference
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws ReaderException
     */
    public function __construct(FileReference $reference)
    {
        if ($reference->getOriginalFile()->exists() === false) {
            throw new ReaderException('Reference original file doesn\'t exists!', 1539959214);
        }

        if (!in_array($reference->getExtension(), self::ALLOWED_EXTENSTIONS)) {
            throw new ReaderException(sprintf(
                'Reference has unallowed file extension "%s"! Allowed Extensions are "%s"',
                $reference->getExtension(),
                implode(',', self::ALLOWED_EXTENSTIONS)
            ), 1514909945);
        }

        switch ($reference->getExtension()) {
            case 'xls':
                $this->readerInstance = new XlsReader();
                break;
            case 'xlsx':
                $this->readerInstance = new XlsxReader();
                break;
            case 'ods':
                $this->readerInstance = new OdsReader();
                break;
            case 'xml':
                $this->readerInstance = new XmlReader();
                break;
            case 'csv':
                $this->readerInstance = new CsvReader();
                break;
            case 'html':
                $this->readerInstance = new HtmlReader();
                break;
        }

        // try to load reference in current reader instance
        $this->setSpreadsheet($this->readerInstance->load($reference->getForLocalProcessing()));
        $this->setSheetIndex(0);
    }

    /**
     * @return IReader
     */
    public function getReader(): IReader
    {
        return $this->readerInstance;
    }

    /**
     * @return Worksheet[]
     */
    public function getSheets(): array
    {
        return $this->getSpreadsheet()->getAllSheets();
    }

    /**
     * @param int $index
     *
     * @return Worksheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function getSheet(int $index = 0): Worksheet
    {
        return $this->getSpreadsheet()->getSheet($index);
    }
}
