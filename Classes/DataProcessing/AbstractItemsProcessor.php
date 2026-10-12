<?php

declare(strict_types=1);

/*
 * This file is part of the Connetation TYPO3 Web Toolkit - T3WTK.
 *
 * Copyright (c) Connetation Web Engineering GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
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

namespace Connetation\T3wtkBase\DataProcessing;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Shared flow for item-list content-element data processors (conn_t3wtk_elements,
 * conn_wiki, conn_dashboard, …):
 *
 *  1. read the tt_content uid,
 *  2. decode the FlexForm settings column,
 *  3. fetch the child records filtered by content_element, ordered by sorting,
 *  4. resolve FAL file references in ONE batched query per field (no N+1),
 *  5. hand rows/settings to the template under the configured variable names.
 *
 * All frontend queries use the FrontendRestrictionContainer instead of manual
 * deleted/hidden predicates, so backend hidden-content preview works.
 */
abstract class AbstractItemsProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $targetVar         = $processorConfiguration['as'] ?? $this->getDefaultTargetVariable();
        $settingsField     = $this->getSettingsField($processorConfiguration);
        $contentElementUid = (int)($processedData['data']['uid'] ?? 0);

        if ($contentElementUid === 0) {
            $processedData[$targetVar] = [];
            $processedData['settings'] = array_replace_recursive(
                $this->getTypoScriptSettings($contentObjectConfiguration),
                $this->defaultSettings($settingsField)
            );
            return $processedData;
        }

        // ─── FlexForm settings ────────────────────────────────────
        $settings = $this->resolveSettings(
            $this->decodeFlexFormSettings($processedData['data'], $settingsField),
            $settingsField
        );

        // ─── Child records ────────────────────────────────────────
        $rows = $this->fetchChildren($this->getTable(), $contentElementUid);

        // ─── Resolve FAL references (batched, one query per field) ─
        foreach ($this->getFalFields() as $fieldName => $rowKey) {
            $rows = $this->attachFirstFileReference($rows, $this->getTable(), $fieldName, $rowKey);
        }

        $rows = $this->postProcessRows($rows, $settings);

        $processedData[$targetVar] = $rows;
        $processedData['settings'] = array_replace_recursive(
            $this->getTypoScriptSettings($contentObjectConfiguration),
            $settings
        );

        return $this->finalizeProcessedData($processedData, $rows, $settings, $settingsField, $contentElementUid);
    }

    /**
     * TypoScript settings of the rendering content object (lib.contentElement.settings:
     * defaultHeaderType, media/lightbox config, …). The FlexForm component settings are
     * merged ON TOP of these — assigning them alone would overwrite Fluid's reserved
     * `settings` variable and blank all TypoScript settings for the whole element,
     * including the shared conn_t3wtk_base layout and header partial.
     */
    protected function getTypoScriptSettings(array $contentObjectConfiguration): array
    {
        return GeneralUtility::makeInstance(TypoScriptService::class)
            ->convertTypoScriptArrayToPlainArray($contentObjectConfiguration['settings.'] ?? []);
    }

    // ─────────────────────────────────────────────────────────────
    // Per-processor configuration
    // ─────────────────────────────────────────────────────────────

    /** Child table holding the item records. */
    abstract protected function getTable(): string;

    /** Template variable the rows are assigned to when no "as" option is set. */
    abstract protected function getDefaultTargetVariable(): string;

    /** Normalize the raw FlexForm settings array. */
    abstract protected function resolveSettings(array $raw, string $settingsField): array;

    /** Settings used when no content element uid is available. */
    abstract protected function defaultSettings(string $settingsField): array;

    /** tt_content column holding the FlexForm settings. */
    abstract protected function getSettingsField(array $processorConfiguration): string;

    /**
     * FAL fields to resolve per row: ['<sys_file_reference fieldname>' => '<row key>'].
     * The first file reference (by sorting_foreign) is assigned, or null.
     */
    protected function getFalFields(): array
    {
        return [];
    }

    /** Hook for per-row enrichment/reordering after FAL resolution. */
    protected function postProcessRows(array $rows, array $settings): array
    {
        return $rows;
    }

    /** Hook to add extra template variables after rows/settings are assigned. */
    protected function finalizeProcessedData(
        array $processedData,
        array $rows,
        array $settings,
        string $settingsField,
        int $contentElementUid
    ): array {
        return $processedData;
    }

    // ─────────────────────────────────────────────────────────────
    // Shared helpers
    // ─────────────────────────────────────────────────────────────

    /** Query builder with frontend restrictions (deleted/hidden/starttime/…, preview-aware). */
    protected function createFrontendQueryBuilder(string $table): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->setRestrictions(
            GeneralUtility::makeInstance(FrontendRestrictionContainer::class)
        );

        return $queryBuilder;
    }

    /** Fetch all child rows of a content element, ordered by sorting. */
    protected function fetchChildren(string $table, int $contentElementUid, string $foreignField = 'content_element'): array
    {
        $queryBuilder = $this->createFrontendQueryBuilder($table);

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $foreignField,
                    $queryBuilder->createNamedParameter($contentElementUid, ParameterType::INTEGER)
                )
            )
            ->orderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /** Decode a FlexForm settings column of the given record into the plain settings array. */
    protected function decodeFlexFormSettings(array $record, string $settingsField): array
    {
        $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
        $flexData = $flexFormService->convertFlexFormContentToArray(
            (string)($record[$settingsField] ?? '')
        );

        return $flexData['settings'] ?? [];
    }

    /**
     * Batched replacement for FileRepository::findByRelation(): resolves the
     * file references of ONE field for MANY records with a single query.
     *
     * @param int[] $uids uids of the records in $table
     * @return array<int, \TYPO3\CMS\Core\Resource\FileReference[]> grouped by uid_foreign,
     *         ordered by sorting_foreign; broken references are skipped silently
     */
    protected function resolveFileReferences(string $table, string $fieldName, array $uids): array
    {
        $uids = array_values(array_map('intval', $uids));
        if ($uids === []) {
            return [];
        }

        $queryBuilder = $this->createFrontendQueryBuilder('sys_file_reference');
        $referenceRows = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->in(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($uids, ArrayParameterType::INTEGER)
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter($table)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter($fieldName)
                )
            )
            ->orderBy('sorting_foreign', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $grouped = [];
        foreach ($referenceRows as $referenceRow) {
            try {
                $reference = $resourceFactory->getFileReferenceObject(
                    (int)$referenceRow['uid'],
                    $referenceRow
                );
            } catch (\Exception $e) {
                // Tolerate missing/broken files exactly like the previous
                // per-row findByRelation() try/catch did.
                continue;
            }
            $grouped[(int)$referenceRow['uid_foreign']][] = $reference;
        }

        return $grouped;
    }

    /** Assign the first file reference of $fieldName (or null) to $rowKey of every row. */
    protected function attachFirstFileReference(array $rows, string $table, string $fieldName, string $rowKey): array
    {
        if ($rows === []) {
            return $rows;
        }

        $grouped = $this->resolveFileReferences($table, $fieldName, array_column($rows, 'uid'));
        foreach ($rows as &$row) {
            $row[$rowKey] = $grouped[(int)$row['uid']][0] ?? null;
        }
        unset($row);

        return $rows;
    }

    /**
     * Guards a FlexForm `type=color` value (with `opacity=true`, so up to 8 hex
     * digits: #RRGGBBAA) before it's trusted as a raw CSS custom property value.
     */
    protected function sanitizeHexColor(string $hex, string $default): string
    {
        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $hex) ? $hex : $default;
    }

    /**
     * Bootstrap text-* class for an alignment that may be 'auto' (a card's
     * subtitle/eyebrow): its own start/center/end, or — for 'auto'/unset —
     * $autoAlign (usually the card's title alignment, like tt_content's
     * eyebrow following header_position). '' when neither is set: the
     * card's own alignment applies.
     */
    protected function resolveAlignClass(mixed $position, string $autoAlign = ''): string
    {
        $align = \in_array($position, ['start', 'center', 'end'], true) ? $position : $autoAlign;

        return \in_array($align, ['start', 'center', 'end'], true) ? 'text-' . $align : '';
    }
}
