<?php

namespace Hoogi91\Spreadsheets\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;

/**
 * Class DataHandlerHook
 * @package Hoogi91\Spreadsheets\Hooks
 */
class DataHandlerHook
{
    private $records = [];

    /**
     * @var FileRepository
     */
    private $fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * Post hook to set default spreadsheet selection for newly created items
     *
     * @param string|mixed $status Status which should be "new" to activate this hook
     * @param string|mixed $table Table which should be "tt_content" to activate this hook
     * @param int|string|mixed $id Temporary ID used to search for real new uid
     * @param array $fieldArray Field array that has been saved to database
     * @param DataHandler $dataHandler Data handler instance
     *
     * @return void
     */
    public function processDatamap_afterDatabaseOperations( // @codingStandardsIgnoreLine
        $status,
        $table,
        $id,
        array $fieldArray,
        DataHandler $dataHandler
    ): void {
        // skip processing for unknown uid, wrong table, status or not updated assets
        $uid = $dataHandler->substNEWwithIDs[$id] ?? (is_int($id) ? $id : null);
        if ($uid === null
            || $table !== 'tt_content'
            || !array_key_exists('tx_spreadsheets_assets', $fieldArray)
            || !in_array($status, ['new', 'update'], true)) {
            return;
        }

        // skip if not spreadsheet table or bodytext is already filled
        $CType = $fieldArray['CType'] ?? $this->getBackendRecordField($uid, 'CType');
        if (!in_array($CType, ['spreadsheets_table', 'spreadsheets_tabs'], true)) {
            return;
        }

        // truncate bodytext after update if assets have been removed
        if ($fieldArray['tx_spreadsheets_assets'] === 0) {
            if ($status === 'update') {
                $dataHandler->updateDB('tt_content', $uid, ['bodytext' => '']);
            }
            return;
        }

        /** @var FileReference[] $relations */
        $relations = $this->fileRepository->findByRelation('tt_content', 'tx_spreadsheets_assets', $uid);
        if (empty($relations)) {
            return;
        }

        // update bodytext to default file selection
        if (empty($this->getBackendRecordField($uid, 'bodytext')) === true) {
            $dataHandler->updateDB('tt_content', $uid, ['bodytext' => 'spreadsheet://' . $relations[0]->getUid()]);
        }
    }

    /**
     * Get backend record field but load entry once
     *
     * @param int $uid UID of tt_content record
     * @param string $field Field to extract
     *
     * @return mixed|null
     */
    private function getBackendRecordField(int $uid, string $field)
    {
        if (!isset($this->records[$uid])) {
            $this->records[$uid] = BackendUtility::getRecord('tt_content', $uid); // @codeCoverageIgnore
        }
        return $this->records[$uid][$field] ?? null;
    }
}
