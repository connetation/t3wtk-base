<?php

declare(strict_types=1);

/*
 * This file is part of the Connetation TYPO3 Web Toolkit - T3WTK.
 *
 * Copyright (c) Connetation Web Engineering GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Connetation\T3wtkBase\Upgrades;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Upgrades\ChattyInterface;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * The toolkit was renamed from "t3kit" to "TYPO3 Web Toolkit" (t3wtk), across every package:
 *
 *   Composer / Site Set  conn/t3kit-*             => connetation/t3wtk-*  (conn/news, conn/events => connetation/…)
 *   Extension key        conn_t3kit_*             => conn_t3wtk_*
 *   DB tables/columns    tx_conn_t3kit_*          => tx_conn_t3wtk_*
 *   PHP namespace        Connetation\T3kit*       => Connetation\T3wtk*
 *
 * This single wizard (shipped by conn_t3wtk_base, which every other package depends on)
 * migrates an existing site for ALL of them, because the rename is purely mechanical:
 *
 *  1. Tables — every "tx_conn_t3kit_*" table is renamed. If a schema sync already created
 *     the new table, it is dropped when empty; a new table that already holds data is left
 *     untouched (reported) rather than silently overwritten.
 *  2. Columns — every "tx_conn_t3kit_*" column on any table (tt_content, pages, the child
 *     tables) is renamed, or — when a schema sync already created the new one empty —
 *     merged into it and dropped. Same "clean rename vs. merge" handling as
 *     RenameExtensionKeySchemaUpdate, for the same reason.
 *  3. String pointers — values that name a table, field, extension path, module or class
 *     as DATA: sys_file_reference / MM "tablenames"+"fieldname", sys_refindex, sys_history,
 *     FlexForms (Form plugin paths), page/user TSconfig, sys_template TypoScript,
 *     be_groups/be_users permissions and modules, backend shortcuts.
 *  4. Site configuration — config/sites/<identifier>/*.yaml: Site Set dependencies
 *     ("conn/t3kit-base" => "connetation/t3wtk-base") and EXT: paths in settings.yaml.
 *  5. Upgrade wizard registry — TYPO3 marks wizards as done by class name, so every
 *     "Connetation\T3kit*" wizard already done is marked done under its new
 *     "Connetation\T3wtk*" name too (otherwise it would run again).
 *
 * Declares NO prerequisites (in particular not DatabaseUpdatedPrerequisite) so it never
 * creates the new, empty tables/columns itself — run it before "extension:setup" /
 * "Analyze Database Structure" if you can. It still copes when those ran first.
 *
 * Flush all caches afterwards (site configuration is cached).
 */
#[UpgradeWizard('connT3wtkBaseMigrateFromT3kit')]
class MigrateFromT3kitUpdate implements UpgradeWizardInterface, ChattyInterface
{
    private const OLD_DB_PREFIX = 'tx_conn_t3kit_';
    private const NEW_DB_PREFIX = 'tx_conn_t3wtk_';

    /** Plain string replacements applied to data columns and site configuration. */
    private const STRING_REPLACEMENTS = [
        'conn_t3kit_'       => 'conn_t3wtk_',
        'Connetation\\T3kit' => 'Connetation\\T3wtk',
        'conn/t3kit-'       => 'connetation/t3wtk-',
    ];

    /** Site Set names that were never "t3kit"-prefixed, only the vendor changed. */
    private const SET_NAME_PATTERN = '#\bconn/(news|events)\b#';
    private const SET_NAME_REPLACEMENT = 'connetation/$1';

    /**
     * Columns that hold table/field/extension/module/class names as data. Pairs whose
     * table or column does not exist on this install are skipped. Every "tablenames" /
     * "fieldname" column on any table (sys_file_reference, sys_category_record_mm, custom
     * MM tables) is added automatically.
     */
    private const DATA_COLUMNS = [
        'sys_refindex'     => ['tablename', 'field', 'ref_table'],
        'sys_history'      => ['tablename'],
        'tt_content'       => ['pi_flexform'],
        'pages'            => ['TSconfig'],
        'sys_template'     => ['constants', 'config', 'include_static_file'],
        'be_groups'        => ['tables_select', 'tables_modify', 'non_exclude_fields', 'explicit_allowdeny', 'groupMods', 'TSconfig', 'custom_options'],
        'be_users'         => ['userMods', 'TSconfig'],
        'sys_be_shortcuts' => ['route', 'arguments'],
        'backend_layout'   => ['config'],
    ];

    private const POINTER_COLUMN_NAMES = ['tablenames', 'fieldname'];

    private ?OutputInterface $output = null;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly Registry $registry,
    ) {}

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function getTitle(): string
    {
        return 'EXT:conn_t3wtk_base: Migrate from "t3kit" (conn_t3kit_*) to "TYPO3 Web Toolkit" (conn_t3wtk_*)';
    }

    public function getDescription(): string
    {
        return 'The toolkit packages were renamed (conn/t3kit-* => connetation/t3wtk-*, extension keys '
            . 'conn_t3kit_* => conn_t3wtk_*). This renames every "tx_conn_t3kit_*" table and column — '
            . 'merging into new ones a prior schema sync may already have created empty — and rewrites '
            . 'stored references (file references, MM relations, FlexForms, TSconfig, TypoScript records, '
            . 'backend permissions/modules), the Site Set dependencies and EXT: paths in config/sites/*/*.yaml, '
            . 'and the done-state of previously executed toolkit upgrade wizards. Flush all caches afterwards.';
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function updateNecessary(): bool
    {
        return $this->getTableActions() !== []
            || $this->getColumnActions() !== []
            || $this->getDataColumnsToUpdate() !== []
            || $this->getSiteConfigurationFilesToUpdate() !== []
            || $this->getRegistryFlagsToCarryOver() !== [];
    }

    public function executeUpdate(): bool
    {
        // See RenameExtensionKeySchemaUpdate::executeUpdate() — structural ALTERs on a wide
        // tt_content are rejected with "Row size too large" under innodb_strict_mode.
        $connection = $this->getConnection();
        $originalStrictMode = (string)$connection->executeQuery('SELECT @@SESSION.innodb_strict_mode')->fetchOne();
        $connection->executeStatement('SET SESSION innodb_strict_mode = 0');

        try {
            $success = $this->migrateTables();
            $this->migrateColumns();
            $this->migrateDataColumns();
            $this->migrateSiteConfiguration();
            $this->carryOverRegistryFlags();
        } finally {
            $connection->executeStatement('SET SESSION innodb_strict_mode = ' . ($originalStrictMode !== '' ? (int)$originalStrictMode : 1));
        }

        return $success;
    }

    // ── 1. Tables ─────────────────────────────────────────────────────────────

    /**
     * @return list<array{old: string, new: string, mode: 'rename'|'replaceEmpty'|'dropEmptyOld'|'conflict'}>
     */
    private function getTableActions(): array
    {
        $connection = $this->getConnection();
        $tables = $connection->createSchemaManager()->listTableNames();

        $actions = [];
        foreach ($tables as $old) {
            if (!str_starts_with($old, self::OLD_DB_PREFIX)) {
                continue;
            }
            $new = self::NEW_DB_PREFIX . substr($old, strlen(self::OLD_DB_PREFIX));

            if (!in_array($new, $tables, true)) {
                $mode = 'rename';
            } elseif ($this->countRows($new) === 0) {
                $mode = 'replaceEmpty';
            } elseif ($this->countRows($old) === 0) {
                $mode = 'dropEmptyOld';
            } else {
                $mode = 'conflict';
            }
            $actions[] = ['old' => $old, 'new' => $new, 'mode' => $mode];
        }

        return $actions;
    }

    private function migrateTables(): bool
    {
        $connection = $this->getConnection();
        $success = true;

        foreach ($this->getTableActions() as $action) {
            $old = $connection->quoteIdentifier($action['old']);
            $new = $connection->quoteIdentifier($action['new']);

            switch ($action['mode']) {
                case 'replaceEmpty':
                    // New table was created empty by a prior schema sync — replace it.
                    $connection->executeStatement('DROP TABLE ' . $new);
                    // no break
                case 'rename':
                    $connection->executeStatement(sprintf('RENAME TABLE %s TO %s', $old, $new));
                    $this->log(sprintf('Renamed table %s => %s', $action['old'], $action['new']));
                    break;
                case 'dropEmptyOld':
                    $connection->executeStatement('DROP TABLE ' . $old);
                    $this->log(sprintf('Dropped empty legacy table %s (%s already holds the data)', $action['old'], $action['new']));
                    break;
                default:
                    // Both hold data — never discard either side automatically.
                    $success = false;
                    $this->log(sprintf(
                        '<error>Table %s and %s both contain records — resolve manually, then re-run this wizard.</error>',
                        $action['old'],
                        $action['new']
                    ));
            }
        }

        return $success;
    }

    // ── 2. Columns ────────────────────────────────────────────────────────────

    /**
     * @return list<array{table: string, old: string, new: string, mode: 'rename'|'merge'}>
     */
    private function getColumnActions(): array
    {
        $rows = $this->getConnection()->executeQuery(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME LIKE ?',
            [$this->escapeLike(self::OLD_DB_PREFIX) . '%']
        )->fetchAllAssociative();

        $existing = [];
        foreach ($this->getConnection()->executeQuery(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME LIKE ?',
            [$this->escapeLike(self::NEW_DB_PREFIX) . '%']
        )->fetchAllAssociative() as $row) {
            $existing[$row['TABLE_NAME'] . '.' . $row['COLUMN_NAME']] = true;
        }

        $actions = [];
        foreach ($rows as $row) {
            $table = $row['TABLE_NAME'];
            $old = $row['COLUMN_NAME'];
            $new = self::NEW_DB_PREFIX . substr($old, strlen(self::OLD_DB_PREFIX));
            $actions[] = [
                'table' => $table,
                'old' => $old,
                'new' => $new,
                'mode' => isset($existing[$table . '.' . $new]) ? 'merge' : 'rename',
            ];
        }

        return $actions;
    }

    private function migrateColumns(): void
    {
        $connection = $this->getConnection();

        foreach ($this->getColumnActions() as $action) {
            $table = $connection->quoteIdentifier($action['table']);
            $old = $connection->quoteIdentifier($action['old']);
            $new = $connection->quoteIdentifier($action['new']);

            if ($action['mode'] === 'merge') {
                $connection->executeStatement(sprintf('UPDATE %s SET %s = %s', $table, $new, $old));
                $connection->executeStatement(sprintf('ALTER TABLE %s DROP COLUMN %s', $table, $old));
                $this->log(sprintf('Merged column %s.%s into %s', $action['table'], $action['old'], $action['new']));
                continue;
            }

            // RENAME COLUMN is metadata-only — see RenameExtensionKeySchemaUpdate::migrateColumns().
            $connection->executeStatement(sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s', $table, $old, $new));
            $this->log(sprintf('Renamed column %s.%s => %s', $action['table'], $action['old'], $action['new']));
        }
    }

    // ── 3. String pointers stored as data ─────────────────────────────────────

    /**
     * @return list<array{table: string, column: string}>
     */
    private function getDataColumnsToUpdate(): array
    {
        $connection = $this->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        $candidates = [];
        foreach (self::DATA_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $candidates[$table . '.' . $column] = ['table' => $table, 'column' => $column];
            }
        }
        foreach ($connection->executeQuery(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME IN (?)',
            [self::POINTER_COLUMN_NAMES],
            [Connection::PARAM_STR_ARRAY]
        )->fetchAllAssociative() as $row) {
            $candidates[$row['TABLE_NAME'] . '.' . $row['COLUMN_NAME']] = ['table' => $row['TABLE_NAME'], 'column' => $row['COLUMN_NAME']];
        }

        $result = [];
        foreach ($candidates as $candidate) {
            if (!in_array($candidate['table'], $tables, true)
                || !$schemaManager->introspectTable($candidate['table'])->hasColumn($candidate['column'])
            ) {
                continue;
            }
            if ($this->countRowsContainingOldStrings($candidate['table'], $candidate['column']) > 0) {
                $result[] = $candidate;
            }
        }

        return $result;
    }

    private function countRowsContainingOldStrings(string $table, string $column): int
    {
        $connection = $this->getConnection();

        return (int)$connection->executeQuery(sprintf(
            'SELECT COUNT(*) FROM %s WHERE %s',
            $connection->quoteIdentifier($table),
            $this->buildContainsOldStringsCondition($column)
        ))->fetchOne();
    }

    private function migrateDataColumns(): void
    {
        $connection = $this->getConnection();

        foreach ($this->getDataColumnsToUpdate() as $candidate) {
            $column = $connection->quoteIdentifier($candidate['column']);
            $expression = $column;
            foreach (self::STRING_REPLACEMENTS as $old => $new) {
                $expression = sprintf('REPLACE(%s, %s, %s)', $expression, $connection->quote($old), $connection->quote($new));
            }

            $affected = $connection->executeStatement(sprintf(
                'UPDATE %s SET %s = %s WHERE %s',
                $connection->quoteIdentifier($candidate['table']),
                $column,
                $expression,
                $this->buildContainsOldStringsCondition($candidate['column'])
            ));
            $this->log(sprintf('Updated %d row(s) in %s.%s', $affected, $candidate['table'], $candidate['column']));
        }
    }

    private function buildContainsOldStringsCondition(string $column): string
    {
        $connection = $this->getConnection();
        $quotedColumn = $connection->quoteIdentifier($column);

        $conditions = [];
        foreach (array_keys(self::STRING_REPLACEMENTS) as $old) {
            $conditions[] = sprintf('%s LIKE %s', $quotedColumn, $connection->quote('%' . $this->escapeLike($old) . '%'));
        }

        return '(' . implode(' OR ', $conditions) . ')';
    }

    // ── 4. Site configuration ─────────────────────────────────────────────────

    /**
     * @return array<string, string> file path => migrated content
     */
    private function getSiteConfigurationFilesToUpdate(): array
    {
        $files = glob(Environment::getConfigPath() . '/sites/*/*.yaml') ?: [];

        $result = [];
        foreach ($files as $file) {
            $content = (string)file_get_contents($file);
            $migrated = preg_replace(
                self::SET_NAME_PATTERN,
                self::SET_NAME_REPLACEMENT,
                strtr($content, self::STRING_REPLACEMENTS)
            );
            if ($migrated !== $content) {
                $result[$file] = $migrated;
            }
        }

        return $result;
    }

    private function migrateSiteConfiguration(): void
    {
        foreach ($this->getSiteConfigurationFilesToUpdate() as $file => $content) {
            if (!is_writable($file)) {
                $this->log(sprintf('<error>%s is not writable — update it manually.</error>', $file));
                continue;
            }
            file_put_contents($file, $content);
            $this->log(sprintf('Updated %s', $file));
        }
    }

    // ── 5. Upgrade wizard done-state ──────────────────────────────────────────

    /**
     * @return array<string, mixed> new class name => stored done value
     */
    private function getRegistryFlagsToCarryOver(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_registry');
        $rows = $queryBuilder
            ->select('entry_key')
            ->from('sys_registry')
            ->where(
                $queryBuilder->expr()->eq('entry_namespace', $queryBuilder->createNamedParameter('installUpdate')),
                $queryBuilder->expr()->like('entry_key', $queryBuilder->createNamedParameter($this->escapeLike('Connetation\\T3kit') . '%'))
            )
            ->executeQuery()
            ->fetchFirstColumn();

        $result = [];
        foreach ($rows as $oldClassName) {
            $value = $this->registry->get('installUpdate', $oldClassName);
            if (!$value) {
                continue;
            }
            $newClassName = strtr($oldClassName, ['Connetation\\T3kit' => 'Connetation\\T3wtk']);
            if (!$this->registry->get('installUpdate', $newClassName)) {
                $result[$newClassName] = $value;
            }
        }

        return $result;
    }

    private function carryOverRegistryFlags(): void
    {
        foreach ($this->getRegistryFlagsToCarryOver() as $newClassName => $value) {
            $this->registry->set('installUpdate', $newClassName, $value);
            $this->log(sprintf('Marked upgrade wizard %s as done', $newClassName));
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getConnection(): Connection
    {
        return $this->connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
    }

    /** Counts ALL rows, including soft-deleted/hidden ones (unlike Connection::count()). */
    private function countRows(string $table): int
    {
        $connection = $this->getConnection();

        return (int)$connection->executeQuery('SELECT COUNT(*) FROM ' . $connection->quoteIdentifier($table))->fetchOne();
    }

    /** Escapes LIKE wildcards and MySQL's LIKE escape character (backslash). */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function log(string $message): void
    {
        $this->output?->writeln('  ' . $message);
    }
}
