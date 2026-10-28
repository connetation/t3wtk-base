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

use Connetation\T3wtkBase\Service\ButtonStyleItemsProcFunc;
use Connetation\T3wtkBase\Utility\CardAlignOptions;
use Connetation\T3wtkBase\Utility\CardDesignOptions;
use Connetation\T3wtkBase\Utility\FontWeightOptions;
use Connetation\T3wtkBase\Utility\RadiusOptions;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

/**
 * Adds an "Appearance" sheet to every georgringer/news plugin that renders a
 * list of news (all of them share Partials/News/List/Items.html)
 */
#[AsEventListener('conn-t3wtk-base/add-newslist-appearance-fields')]
final class AddNewsListAppearanceFields
{
    private const LIST_PLUGINS = [
        'news_pi1',
        'news_newsliststicky',
        'news_newsselectedlist',
        'news_newssearchresult',
    ];

    public function __invoke(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (($identifier['tableName'] ?? '') !== 'tt_content'
            || ($identifier['fieldName'] ?? '') !== 'pi_flexform'
            || !\in_array($identifier['dataStructureKey'] ?? '', self::LIST_PLUGINS, true)
        ) {
            return;
        }

        $alignItems = [
            ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:align.left', 'value' => 'start'],
            ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:align.center', 'value' => 'center'],
            ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:align.right', 'value' => 'end'],
        ];

        $dataStructure = $event->getDataStructure();
        $dataStructure['sheets']['connAppearance'] = [
            'ROOT' => [
                'sheetTitle' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.appearance',
                'type' => 'array',
                'el' => [
                    'settings.appearance.titleAlign' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.titleAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    // Title tag (SEO level) and visual size — rendered with conn:heading.
                    'settings.appearance.titleTag' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.titleTag',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'default' => 'h3',
                            'items' => [
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_tag.h2', 'value' => 'h2'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_tag.h3', 'value' => 'h3'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_tag.h4', 'value' => 'h4'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_tag.h5', 'value' => 'h5'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_tag.h6', 'value' => 'h6'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_tag.p', 'value' => 'p'],
                            ],
                        ],
                    ],
                    'settings.appearance.titleSize' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.titleSize',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'default' => 'h4',
                            'items' => [
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_size.none', 'value' => 'none'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_size.h1', 'value' => 'h1'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_size.h2', 'value' => 'h2'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_size.h3', 'value' => 'h3'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_size.h4', 'value' => 'h4'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_size.h5', 'value' => 'h5'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:heading_size.h6', 'value' => 'h6'],
                            ],
                        ],
                    ],
                    // Card font weights — Bootstrap fw-* utilities (FontWeightOptions);
                    // list cards keep their bold title by default.
                    'settings.appearance.titleWeight' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.titleWeight',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'default' => 'fw-bold',
                            'itemsProcFunc' => FontWeightOptions::class . '->addItems',
                        ],
                    ],
                    'settings.appearance.textWeight' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.textWeight',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'default' => 'fw-normal',
                            'itemsProcFunc' => FontWeightOptions::class . '->addItems',
                        ],
                    ],
                    'settings.appearance.metaAlign' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.metaAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.bodyAlign' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.bodyAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.linkAlign' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.linkAlign',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => $alignItems,
                            'default' => 'start',
                        ],
                    ],
                    'settings.appearance.cardStyle' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.cardStyle',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:cardStyle.border', 'value' => ''],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:cardStyle.shadow', 'value' => 'shadow'],
                                ['label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:cardStyle.flat', 'value' => 'flat'],
                            ],
                            'default' => '',
                        ],
                    ],
                    'settings.appearance.cardBorderColor' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.cardBorderColor',
                        'config' => [
                            'type' => 'color',
                            'opacity' => true,
                            'default' => '',
                        ],
                    ],
                    'settings.appearance.cardBorderRadius' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:flexform.cardBorderRadius',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'default' => RadiusOptions::THEME_DEFAULT,
                            'itemsProcFunc' => RadiusOptions::class . '->addItems',
                        ],
                    ],
                    'settings.appearance.cardAlign' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_align',
                        'description' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_align.description',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'default' => CardAlignOptions::DEFAULT,
                            'itemsProcFunc' => CardAlignOptions::class . '->addItems',
                        ],
                    ],
                    'settings.appearance.cardDesign' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_design',
                        'onChange' => 'reload',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'default' => CardDesignOptions::DEFAULT,
                            'itemsProcFunc' => CardDesignOptions::class . '->addItems',
                        ],
                    ],
                    'settings.appearance.cardBackgroundColor' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_design.background_color',
                        'description' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_design.background_color.description',
                        'displayCond' => 'FIELD:settings.appearance.cardDesign:IN:image,color',
                        'config' => [
                            'type' => 'color',
                            'opacity' => true,
                            'default' => '',
                        ],
                    ],
                    'settings.appearance.cardOverlayColor' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_design.overlay_color',
                        'description' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_design.overlay_color.description',
                        'displayCond' => 'FIELD:settings.appearance.cardDesign:=:image',
                        'config' => [
                            'type' => 'color',
                            'opacity' => true,
                            'default' => '',
                        ],
                    ],
                    'settings.appearance.cardTextColor' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_design.text_color',
                        'displayCond' => 'FIELD:settings.appearance.cardDesign:IN:image,color',
                        'config' => [
                            'type' => 'color',
                            'opacity' => true,
                            'default' => '',
                        ],
                    ],
                    'settings.appearance.linkStyle' => [
                        'label' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:link_style',
                        'description' => 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:link_style.description',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'default' => '',
                            'itemsProcFunc' => ButtonStyleItemsProcFunc::class . '->addItemsWithDefault',
                        ],
                    ],
                ],
            ],
        ];
        $event->setDataStructure($dataStructure);
    }
}
