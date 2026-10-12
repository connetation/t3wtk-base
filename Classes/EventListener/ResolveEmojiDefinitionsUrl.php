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

namespace Connetation\T3wtkBase\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\RteCKEditor\Form\Element\Event\AfterPrepareConfigurationForEditorEvent;

#[AsEventListener('conn-t3wtk-base/resolve-emoji-definitions-url')]
final class ResolveEmojiDefinitionsUrl
{
    public function __construct(
        private readonly SystemResourceFactory $systemResourceFactory,
        private readonly SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    public function __invoke(AfterPrepareConfigurationForEditorEvent $event): void
    {
        $configuration = $event->getConfiguration();
        if (!\array_key_exists('emoji', $configuration)) {
            return;
        }

        $resource = $this->systemResourceFactory->createPublicResource(
            'EXT:conn_t3wtk_base/Resources/Public/JavaScript/CKEditor/emoji-en.json'
        );
        $relativeUri = (string)$this->resourcePublisher->generateUri($resource, null);
        $configuration['emoji']['definitionsUrl'] = rtrim(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), '/') . $relativeUri;
        $event->setConfiguration($configuration);
    }
}
