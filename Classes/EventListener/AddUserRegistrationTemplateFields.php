<?php

declare(strict_types=1);

namespace Connetation\Base\EventListener;

use DERHANSEN\SfEventMgt\Hooks\TemplateLayouts;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

/**
 * derhansen/sf_event_mgt's "My registrations" plugin (Piuserreg) is the only
 * one of its FlexForms without a "Template" sheet — every other plugin
 * (Pieventlist, Pieventdetail, Pieventregistration, ...) already has one.
 * This injects a "Template" sheet into Piuserreg's FlexForm with:
 *
 * - settings.templateLayout: the same Liste/Karten switch as the other
 *   plugins, driven by the same TemplateLayouts::user_templateLayout()
 *   itemsProcFunc (options supplied via Page TSConfig
 *   tx_sfeventmgt.templateLayouts, see Configuration/Sets/Events/page.tsconfig).
 * - settings.hideEventImage: a checkbox to hide the event image in
 *   List/Card.fluid.html and List/ListItem.fluid.html. Named as a negative
 *   ("hide", not "show") so that older content elements which never saved
 *   this field (settings.hideEventImage simply absent from Extbase's
 *   settings array) fall back to falsy/unchecked — matching the desired
 *   default of "show the image if the event has one".
 */
#[AsEventListener('conn-base/add-userregistration-template-fields')]
final class AddUserRegistrationTemplateFields
{
    public function __invoke(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (($identifier['tableName'] ?? '') !== 'tt_content'
            || ($identifier['fieldName'] ?? '') !== 'pi_flexform'
            || ($identifier['dataStructureKey'] ?? '') !== 'sfeventmgt_piuserreg'
        ) {
            return;
        }

        $dataStructure = $event->getDataStructure();
        $dataStructure['sheets']['template'] = [
            'ROOT' => [
                'sheetTitle' => 'Template',
                'type' => 'array',
                'el' => [
                    'settings.templateLayout' => [
                        'label' => 'Template Layout',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'itemsProcFunc' => TemplateLayouts::class . '->user_templateLayout',
                            'items' => [
                                ['label' => '', 'value' => ''],
                            ],
                        ],
                    ],
                    'settings.hideEventImage' => [
                        'label' => 'Veranstaltungsbild ausblenden',
                        'config' => [
                            'type' => 'check',
                            'renderType' => 'checkboxToggle',
                            'items' => [
                                ['label' => ''],
                            ],
                            'default' => 0,
                        ],
                    ],
                ],
            ],
        ];
        $event->setDataStructure($dataStructure);
    }
}
