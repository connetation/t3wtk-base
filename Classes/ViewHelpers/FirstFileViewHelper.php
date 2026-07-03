<?php

declare(strict_types=1);

namespace Connetation\Base\ViewHelpers;

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Resolves the first FAL file attached to a single-file TCA relation field,
 * without requiring an Extbase domain model or a per-CType dataProcessing wire-up.
 *
 * Usage: <f:variable name="bgFile" value="{conn:firstFile(uid: data.uid, field: 'tx_conn_base_bg_image')}" />
 */
final class FirstFileViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('table', 'string', 'Database table of the record', false, 'tt_content');
        $this->registerArgument('uid', 'int', 'Record uid', true);
        $this->registerArgument('field', 'string', 'FAL relation field name', true);
    }

    public function render(): ?FileReference
    {
        $uid = (int)$this->arguments['uid'];
        if ($uid <= 0) {
            return null;
        }

        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $files = $fileRepository->findByRelation((string)$this->arguments['table'], (string)$this->arguments['field'], $uid);

        return $files[0] ?? null;
    }
}
