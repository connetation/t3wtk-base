<?php

declare(strict_types=1);

namespace Connetation\T3kitBase\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * The extension key changed from "conn_base" to "conn_t3kit_base", which moves every
 * "tx_conn_base_*" column this package owns — on both tt_content and pages — to
 * "tx_conn_t3kit_base_*". This wizard migrates the physical columns in place so existing
 * content/page settings (backgrounds, header/subheader styling, breadcrumb settings, …)
 * keep working.
 *
 * Handles BOTH of these situations, because in practice a schema sync ("Analyze Database
 * Structure", `database:updateschema`, or another upgrade wizard's DatabaseUpdatedPrerequisite
 * firing first) can easily run before this wizard does — since the TCA already declares the
 * NEW names, that sync creates the new columns empty, alongside the old ones which still
 * hold the real data:
 *
 *  1. Clean case — only the OLD column exists: rename it directly (ALTER TABLE ... CHANGE),
 *     preserving the exact original column definition.
 *  2. Merge case — BOTH exist (old with real data, new already created empty by a schema
 *     sync): copy the data from old into new, then drop old.
 *
 * This wizard deliberately declares NO prerequisites (in particular not
 * DatabaseUpdatedPrerequisite) so it never triggers that schema-add itself — but it cannot
 * stop something else from doing so first, hence handling both cases above.
 *
 * Also fixes sys_file_reference: tx_conn_base_bg_image (a FAL background-image field on
 * tt_content) is referenced by sys_file_reference.fieldname as a plain string, not a real
 * foreign key — a column rename alone leaves it pointing at a name that no longer exists,
 * silently breaking every content element's background image.
 */
#[UpgradeWizard('connT3kitBaseRenameExtensionKeySchema')]
readonly class RenameExtensionKeySchemaUpdate implements UpgradeWizardInterface
{
    private const OLD_PREFIX = 'tx_conn_base_';
    private const NEW_PREFIX = 'tx_conn_t3kit_base_';

    private const TT_CONTENT_COLUMN_SUFFIXES = [
        'content_width',
        'bg_type', 'bg_color', 'bg_text_color', 'bg_image', 'bg_mode',
        'bg_padding_before', 'bg_padding_after',
        'header_class',
        'subheader_class', 'subheader_layout',
        'subheader_eyebrow', 'subheader_eyebrow_position', 'subheader_eyebrow_color',
    ];

    private const PAGES_COLUMN_SUFFIXES = [
        'hide_breadcrumb',
        'breadcrumb_position',
        'bg_color',
        'text_color',
        'heading_color',
    ];

    /** Subset of TT_CONTENT_COLUMN_SUFFIXES that are FAL relations. */
    private const TT_CONTENT_FAL_FIELD_SUFFIXES = [
        'bg_image',
    ];

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'EXT:conn_t3kit_base: Rename "tx_conn_base_*" columns to "tx_conn_t3kit_base_*"';
    }

    public function getDescription(): string
    {
        return sprintf(
            'The extension key changed from "conn_base" to "conn_t3kit_base". This migrates the '
            . '%d "tx_conn_base_*" columns on tt_content and %d on pages to their '
            . '"tx_conn_t3kit_base_*" equivalent, preserving all data — including merging in '
            . 'columns a prior schema sync may already have created empty under the new name — '
            . 'and repairs sys_file_reference for the background-image field.',
            count(self::TT_CONTENT_COLUMN_SUFFIXES),
            count(self::PAGES_COLUMN_SUFFIXES)
        );
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function updateNecessary(): bool
    {
        return $this->getColumnActions('tt_content', self::TT_CONTENT_COLUMN_SUFFIXES) !== []
            || $this->getColumnActions('pages', self::PAGES_COLUMN_SUFFIXES) !== []
            || $this->getStaleFileReferenceFieldNames() !== [];
    }

    public function executeUpdate(): bool
    {
        // tt_content accumulates columns from every installed extension over the life of a
        // site; on many real installs its theoretical maximum row size (the sum of every
        // VARCHAR/CHAR column's max possible byte length) is already over InnoDB's
        // 8126-byte-not-counting-BLOBs limit, even though actual stored data fits fine.
        // MariaDB/MySQL re-validates that theoretical maximum on ANY structural ALTER
        // touching a VARCHAR/CHAR column — rename, drop, whatever — and rejects it with
        // "Row size too large", regardless of how little the operation actually changes.
        // innodb_strict_mode=0 downgrades that check to a warning, which is the standard,
        // TYPO3-core-documented way to get such an ALTER through; restore the session's
        // original value afterward rather than assuming it was 1.
        $connection = $this->connectionPool->getConnectionForTable('tt_content');
        $originalStrictMode = (string)$connection->executeQuery('SELECT @@SESSION.innodb_strict_mode')->fetchOne();
        $connection->executeStatement('SET SESSION innodb_strict_mode = 0');

        try {
            $this->migrateColumns('tt_content', self::TT_CONTENT_COLUMN_SUFFIXES);
            $this->migrateColumns('pages', self::PAGES_COLUMN_SUFFIXES);
            $this->migrateFileReferences();
        } finally {
            $connection->executeStatement('SET SESSION innodb_strict_mode = ' . ($originalStrictMode !== '' ? (int)$originalStrictMode : 1));
        }

        return true;
    }

    /**
     * @param list<string> $suffixes
     * @return list<array{old: string, new: string, mode: 'rename'|'merge'}>
     */
    private function getColumnActions(string $table, array $suffixes): array
    {
        $connection = $this->connectionPool->getConnectionForTable($table);
        $schemaTable = $connection->createSchemaManager()->introspectTable($table);

        $actions = [];
        foreach ($suffixes as $suffix) {
            $old = self::OLD_PREFIX . $suffix;
            $new = self::NEW_PREFIX . $suffix;

            if (!$schemaTable->hasColumn($old)) {
                continue;
            }

            $actions[] = ['old' => $old, 'new' => $new, 'mode' => $schemaTable->hasColumn($new) ? 'merge' : 'rename'];
        }

        return $actions;
    }

    /**
     * @param list<string> $suffixes
     */
    private function migrateColumns(string $table, array $suffixes): void
    {
        $connection = $this->connectionPool->getConnectionForTable($table);
        $platform = $connection->getDatabasePlatform();

        foreach ($this->getColumnActions($table, $suffixes) as $action) {
            if ($action['mode'] === 'merge') {
                // New column was already created (empty/default) by a prior schema sync —
                // copy the real values over, then drop the old column.
                $connection->executeStatement(sprintf(
                    'UPDATE %s SET %s = %s',
                    $platform->quoteIdentifier($table),
                    $platform->quoteIdentifier($action['new']),
                    $platform->quoteIdentifier($action['old'])
                ));
                $connection->executeStatement(sprintf(
                    'ALTER TABLE %s DROP COLUMN %s',
                    $platform->quoteIdentifier($table),
                    $platform->quoteIdentifier($action['old'])
                ));
                continue;
            }

            // Clean rename: RENAME COLUMN is metadata-only (no table rebuild), unlike
            // CHANGE COLUMN which forces InnoDB to rebuild the whole row layout even when
            // only the name changes — on a table with as many columns as tt_content that
            // rebuild can exceed InnoDB's 8126-byte row-size limit ("Row size too large")
            // depending on the table's ROW_FORMAT, even though nothing about the actual
            // column definition is changing.
            $connection->executeStatement(sprintf(
                'ALTER TABLE %s RENAME COLUMN %s TO %s',
                $platform->quoteIdentifier($table),
                $platform->quoteIdentifier($action['old']),
                $platform->quoteIdentifier($action['new'])
            ));
        }
    }

    /**
     * @return list<array{old: string, new: string}>
     */
    private function getStaleFileReferenceFieldNames(): array
    {
        $connection = $this->connectionPool->getConnectionForTable('sys_file_reference');

        $pairs = [];
        foreach (self::TT_CONTENT_FAL_FIELD_SUFFIXES as $suffix) {
            $old = self::OLD_PREFIX . $suffix;
            $new = self::NEW_PREFIX . $suffix;

            $count = (int)$connection->count('uid', 'sys_file_reference', [
                'tablenames' => 'tt_content',
                'fieldname' => $old,
            ]);
            if ($count > 0) {
                $pairs[] = ['old' => $old, 'new' => $new];
            }
        }

        return $pairs;
    }

    private function migrateFileReferences(): void
    {
        $connection = $this->connectionPool->getConnectionForTable('sys_file_reference');

        foreach ($this->getStaleFileReferenceFieldNames() as $pair) {
            $connection->update(
                'sys_file_reference',
                ['fieldname' => $pair['new']],
                ['tablenames' => 'tt_content', 'fieldname' => $pair['old']]
            );
        }
    }
}
