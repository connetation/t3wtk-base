<?php

declare(strict_types=1);

namespace Connetation\T3kitBase\EventListener;

use Connetation\T3kitBase\Utility\RadiusOptions;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

/**
 * Adds an "Appearance" sheet to derhansen/sf_event_mgt's List/Card plugin (Pieventlist)
 */
#[AsEventListener('conn-t3kit-base/add-eventlist-appearance-fields')]
final class AddEventListAppearanceFields
{
    public function __invoke(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (($identifier['tableName'] ?? '') !== 'tt_content'
            || ($identifier['fieldName'] ?? '') !== 'pi_flexform'
            || ($identifier['dataStructureKey'] ?? '') !== 'sfeventmgt_pieventlist'
        ) {
            return;
        }

        $alignItems = [
            ['label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:align.left', 'value' => 'start'],
            ['label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:align.center', 'value' => 'center'],
            ['label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:align.right', 'value' => 'end'],
        ];

        $dataStructure = $event->getDataStructure();
        $dataStructure['sheets']['connAppearance'] = [
            'ROOT' => [
                'sheetTitle' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:flexform.appearance',
                'type' => 'array',
                'el' => [
                    'settings.appearance.titleAlign' => [
                        'label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:flexform.titleAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.metaAlign' => [
                        'label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:flexform.metaAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.bodyAlign' => [
                        'label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:flexform.bodyAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.linkAlign' => [
                        'label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:flexform.linkAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.cardStyle' => [
                        'label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:flexform.cardStyle',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                ['label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:cardStyle.border', 'value' => ''],
                                ['label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:cardStyle.shadow', 'value' => 'shadow'],
                                ['label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:cardStyle.flat', 'value' => 'flat'],
                            ],
                            'default' => '',
                        ],
                    ],
                    'settings.appearance.cardBorderColor' => [
                        'label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:flexform.cardBorderColor',
                        'config' => [
                            'type' => 'color',
                            'opacity' => true,
                            'default' => '',
                        ],
                    ],
                    'settings.appearance.cardBorderRadius' => [
                        'label' => 'LLL:EXT:conn_t3kit_base/Resources/Private/Language/locallang_be.xlf:flexform.cardBorderRadius',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'default' => RadiusOptions::THEME_DEFAULT,
                            'itemsProcFunc' => RadiusOptions::class . '->addItems',
                        ],
                    ],
                ],
            ],
        ];
        $event->setDataStructure($dataStructure);
    }
}
