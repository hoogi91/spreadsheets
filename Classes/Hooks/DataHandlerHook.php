<?php

declare(strict_types=1);

namespace Hoogi91\Spreadsheets\Hooks;

use Hoogi91\Spreadsheets\Domain\ValueObject\DsnValueObject;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;

class DataHandlerHook
{
    /**
     * @var array<int, array<mixed>|null>
     */
    private array $records = [];

    /**
     * @var array<string, array<string, array<string, array<string>>>>
     */
    private array $activationTypes = [];

    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly ConnectionPool $connectionPool
    ) {
        foreach ($GLOBALS['TCA'] as $table => $tca) {
            $table = (string)$table;
            foreach ($tca['columns'] ?? [] as $column => $conf) {
                if (
                    isset($conf['config']['renderType'], $conf['config']['uploadField'])
                    && $conf['config']['renderType'] === 'spreadsheetInput'
                ) {
                    $this->activationTypes[$table]['*'][(string)$conf['config']['uploadField']][] = $column;
                }
            }

            foreach ($GLOBALS['TCA'][$table]['types'] as $CType => $type) {
                $CType = (string)$CType;
                foreach ($type['columnsOverrides'] ?? [] as $column => $conf) {
                    if (
                        isset($conf['config']['renderType'], $conf['config']['uploadField'])
                        && $conf['config']['renderType'] === 'spreadsheetInput'
                    ) {
                        $this->activationTypes[$table][$CType][(string)$conf['config']['uploadField']][] = $column;
                    }
                }
            }
        }
    }

    /**
     * @param string|mixed $status Status which should be "new" to activate this hook
     * @param string|mixed $table Table which should be "tt_content" to activate this hook
     * @param int|string|mixed $id Temporary ID used to search for real new uid
     * @param array<mixed> $fieldArray Field array that has been saved to database
     * @param DataHandler $dataHandler Data handler instance
     */
    public function processDatamap_afterDatabaseOperations( // @codingStandardsIgnoreLine
        $status,
        $table,
        $id,
        array $fieldArray,
        DataHandler $dataHandler
    ): void {
        // skip processing for not found uid or irrelevant status
        $uid = $dataHandler->substNEWwithIDs[$id] ?? (is_int($id) ? $id : null);
        if ($uid === null || !is_string($table) || !in_array($status, ['new', 'update'], true)) {
            return;
        }

        // ignore if handler should not process for table and/or CType
        $CType = $fieldArray['CType'] ?? $this->getBackendRecordField($uid, $table, 'CType');
        if (!isset($this->activationTypes[$table]['*']) && !isset($this->activationTypes[$table][$CType])) {
            return;
        }

        $activationConfig = $this->activationTypes[$table][$CType] ?? $this->activationTypes[$table]['*'];
        foreach ($activationConfig as $uploadField => $renderFields) {
            // truncate render fields after update if assets have been removed
            if (($fieldArray[$uploadField] ?? 1) === 0) {
                if ($status === 'update') {
                    $this->connectionPool
                        ->getConnectionForTable($table)
                        ->update($table, array_fill_keys($renderFields, ''), ['uid' => $uid]);
                }

                continue;
            }

            // if upload fields was filled we get it's relations and start to update all render fields if required
            /** @var array<FileReference> $relations */
            $relations = $this->fileRepository->findByRelation($table, $uploadField, $uid);
            foreach ($renderFields as $renderField) {
                $this->setSpreadsheetValue($uid, $table, $status, $renderField, $relations);
            }
        }
    }

    /**
     * @param int $uid UID of chart record
     * @param string $table Table to update
     * @param string $status Status of current record update
     * @param string $field Field to update spreadsheet value
     * @param array<FileReference> $relations File relations found
     *
     */
    private function setSpreadsheetValue(
        int $uid,
        string $table,
        string $status,
        string $field,
        array $relations
    ): void {
        if (empty($relations)) {
            return;
        }

        // if backend record field is currently empty we pre-select with first relation
        $fieldValue = $this->getBackendRecordField($uid, $table, $field);
        if (empty($fieldValue) === true) {
            $this->connectionPool
                ->getConnectionForTable($table)
                ->update($table, [$field => 'spreadsheet://' . $relations[0]->getUid()], ['uid' => $uid]);
        } elseif ($status === 'new' && is_string($fieldValue)) {
            $dsn = $this->getTranslatedSpreadsheetDsn(
                DsnValueObject::createFromDSN($fieldValue),
                $relations
            );
            if ($dsn !== null) {
                $this->connectionPool
                    ->getConnectionForTable($table)
                    ->update($table, [$field => $dsn], ['uid' => $uid]);
            }
        }
    }

    /**
     * @param DsnValueObject $dsn Original DSN
     * @param array<FileReference> $references File relations found
     *
     */
    private function getTranslatedSpreadsheetDsn(DsnValueObject $dsn, array $references): ?string
    {
        foreach ($references as $reference) {
            if ($reference->getReferenceProperty('l10n_parent') === $dsn->getFileReference()) {
                return str_replace(
                    'spreadsheet://' . $dsn->getFileReference(),
                    'spreadsheet://' . $reference->getUid(),
                    $dsn->getDsn()
                );
            }
        }

        return null;
    }

    /**
     * @param int $uid UID of record
     * @param string $table Table to get record from
     * @param string $field Field to extract
     */
    private function getBackendRecordField(int $uid, string $table, string $field): mixed
    {
        if (!isset($this->records[$uid])) {
            $this->records[$uid] = BackendUtility::getRecord($table, $uid); // @codeCoverageIgnore
        }

        return $this->records[$uid][$field] ?? null;
    }
}
