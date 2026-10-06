<?php

declare(strict_types=1);

namespace Connetation\T3wtkBase\Upgrades;

use Connetation\T3wtkBase\Utility\RadiusOptions;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * The per-element "Border radius" option used to have "medium" mean "follow the
 * site's Corner radius setting". It now has a dedicated "theme" option for that
 * (the new default), and "medium" is a fixed 12px override like small/large.
 *
 * Stored "medium" values almost always come from the old default rather than a
 * deliberate choice, so this rewrites them to "theme" — existing elements keep
 * following the site setting. Run it once when updating; after that, "medium"
 * is only ever set on purpose and must not be touched again.
 *
 * Only rewrites the radius fields (other FlexForm fields also use "medium",
 * e.g. gap or scrim strength), in tt_content.pi_flexform (news/events list
 * plugins) and every tx_conn_t3wtk_*_settings FlexForm column of conn/* elements.
 *
 * Sites that already ran this wizard under its pre-t3wtk class name (TYPO3 tracks done
 * wizards by class name) must not run it again — see LEGACY_CLASS_NAME.
 */
#[UpgradeWizard('connT3wtkBaseRadiusThemeOption')]
readonly class RadiusThemeOptionUpdate implements UpgradeWizardInterface
{
    private const TABLE = 'tt_content';

    /** Class name before the t3kit => t3wtk rename; its done-flag counts as ours. */
    private const LEGACY_CLASS_NAME = 'Connetation\\T3kitBase\\Upgrades\\RadiusThemeOptionUpdate';

    private const PATTERN = '#(<field index="settings\.(?:borderRadius|cardBorderRadius|appearance\.cardBorderRadius)">\s*<value index="vDEF">)medium(</value>)#';

    public function __construct(
        private ConnectionPool $connectionPool,
        private Registry $registry,
    ) {}

    public function getTitle(): string
    {
        return 'EXT:conn_t3wtk_base: "Border radius" option "Medium" → "Theme default"';
    }

    public function getDescription(): string
    {
        return 'Element "Border radius" options got a separate "Theme default" (follows the site\'s Corner '
            . 'radius setting); "Medium" is now a fixed 12px override. Existing elements storing "medium" '
            . '(the former default) are switched to "Theme default" so they keep following the site setting.';
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function updateNecessary(): bool
    {
        if ($this->registry->get('installUpdate', self::LEGACY_CLASS_NAME)) {
            return false;
        }

        return $this->findUpdates() !== [];
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        foreach ($this->findUpdates() as $uid => $changes) {
            $connection->update(self::TABLE, $changes, ['uid' => $uid]);
        }

        return true;
    }

    /**
     * @return array<int, array<string, string>> uid => [column => rewritten FlexForm XML]
     */
    private function findUpdates(): array
    {
        $columns = $this->getFlexFormColumns();
        if ($columns === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('uid', ...$columns)->from(self::TABLE);
        $constraints = [];
        foreach ($columns as $column) {
            $constraints[] = $queryBuilder->expr()->like($column, $queryBuilder->createNamedParameter('%adius%'));
        }
        $rows = $queryBuilder->where($queryBuilder->expr()->or(...$constraints))->executeQuery()->fetchAllAssociative();

        $updates = [];
        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $xml = (string)($row[$column] ?? '');
                $rewritten = preg_replace(self::PATTERN, '${1}' . RadiusOptions::THEME_DEFAULT . '${2}', $xml);
                if (\is_string($rewritten) && $rewritten !== $xml) {
                    $updates[(int)$row['uid']][$column] = $rewritten;
                }
            }
        }

        return $updates;
    }

    /** @return list<string> */
    private function getFlexFormColumns(): array
    {
        $table = $this->connectionPool->getConnectionForTable(self::TABLE)
            ->createSchemaManager()
            ->introspectTable(self::TABLE);

        $columns = [];
        foreach ($table->getColumns() as $column) {
            $name = $column->getName();
            if ($name === 'pi_flexform' || preg_match('/^tx_conn_t3wtk_[a-z0-9_]+_settings$/', $name)) {
                $columns[] = $name;
            }
        }

        return $columns;
    }
}
