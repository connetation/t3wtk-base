<?php

declare(strict_types=1);

namespace Connetation\Base\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

/**
 * Adds an "Alignment" sheet to georgringer/news' List/Card plugin (news_pi1),
 * read by Partials/News/List/{Item,Card}.html as
 * settings.appearance.{titleAlign,metaAlign,bodyAlign,linkAlign}. Mirrors
 * AddEventListAppearanceFields for derhansen/sf_event_mgt.
 *
 * Deliberately NOT done via ExtensionManagementUtility::addPiFlexFormValue()
 * (used in ext_localconf.php for older TYPO3 versions): that API is
 * deprecated in v14 and now just overwrites the ds pointer outright rather
 * than merging sheets, and georgringer/news sets its own ds in
 * Configuration/TCA/Overrides/tt_content.php — which always runs after every
 * extension's ext_localconf.php — so that assignment would unconditionally
 * win and this sheet would never appear. This event fires after the ds has
 * already been resolved and parsed, so it can merge into it safely.
 */
#[AsEventListener('conn-base/add-newslist-appearance-fields')]
final class AddNewsListAppearanceFields
{
    public function __invoke(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (($identifier['tableName'] ?? '') !== 'tt_content'
            || ($identifier['fieldName'] ?? '') !== 'pi_flexform'
            || ($identifier['dataStructureKey'] ?? '') !== 'news_pi1'
        ) {
            return;
        }

        $alignItems = [
            ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:align.left', 'value' => 'start'],
            ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:align.center', 'value' => 'center'],
            ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:align.right', 'value' => 'end'],
        ];

        $dataStructure = $event->getDataStructure();
        $dataStructure['sheets']['connAppearance'] = [
            'ROOT' => [
                'sheetTitle' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:flexform.appearance',
                'type' => 'array',
                'el' => [
                    'settings.appearance.titleAlign' => [
                        'label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:flexform.titleAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.metaAlign' => [
                        'label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:flexform.metaAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.bodyAlign' => [
                        'label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:flexform.bodyAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.linkAlign' => [
                        'label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:flexform.linkAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                ],
            ],
        ];
        $event->setDataStructure($dataStructure);
    }
}
