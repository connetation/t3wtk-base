<?php

declare(strict_types=1);

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
