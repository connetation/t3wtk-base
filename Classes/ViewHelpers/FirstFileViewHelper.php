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

namespace Connetation\T3wtkBase\ViewHelpers;

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Resolves the first FAL file attached to a single-file TCA relation field,
 * without requiring an Extbase domain model or a per-CType dataProcessing wire-up.
 *
 * Usage: <f:variable name="bgFile" value="{conn:firstFile(uid: data.uid, field: 'tx_conn_t3wtk_base_bg_image')}" />
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
