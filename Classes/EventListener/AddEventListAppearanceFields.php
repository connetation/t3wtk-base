<?php

declare(strict_types=1);

namespace Connetation\Base\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

/**
 * Adds an "Alignment" sheet to derhansen/sf_event_mgt's List/Card plugin
 * (Pieventlist), read by Partials/Events/Event/{ListItem,Card}.fluid.html as
 * settings.appearance.{titleAlign,metaAlign,linkAlign}. Mirrors
 * AddNewsListAppearanceFields for georgringer/news' List/Card plugin, and
 * follows the same pattern already used by AddUserRegistrationTemplateFields
 * for extending another sf_event_mgt plugin's FlexForm.
 */
#[AsEventListener('conn-base/add-eventlist-appearance-fields')]
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
