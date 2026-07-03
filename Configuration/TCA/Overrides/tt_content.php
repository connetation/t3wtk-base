<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// Content element registrations live in EXT:conn_elements (site-level dependency).
// Add conn_base CE overrides here if needed.

// ─── Section background – global field for ALL content element types ─────
// Rendered by conn_base's ContentElements/Default.html layout, injected at a
// higher priority than any other Layout named "Default" for every CType that
// (directly or transitively) copies `lib.contentElement` — see
// Configuration/TypoScript/ContentElementBackgrounds.typoscript. Any future
// content element extension gets this automatically as long as it depends on
// conn_base and builds on lib.contentElement, which is the standard pattern.
ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'tx_conn_base_content_width' => [
        'label'       => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:content_width',
        'displayCond' => 'FIELD:frame_class:=:none',
        'config'      => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'default'    => '',
            'items'      => [
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:content_width.default', 'value' => ''],
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:content_width.full',    'value' => 'full'],
            ],
        ],
    ],
    'tx_conn_base_bg_type' => [
        'label'  => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_type',
        'config' => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'default'    => '',
            'items'      => [
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_type.none',  'value' => ''],
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_type.color', 'value' => 'color'],
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_type.image', 'value' => 'image'],
            ],
        ],
    ],
    'tx_conn_base_bg_color' => [
        'label'       => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_color',
        'displayCond' => 'FIELD:tx_conn_base_bg_type:=:color',
        'config'      => [
            'type'    => 'color',
            'default' => '',
        ],
    ],
    'tx_conn_base_bg_text_color' => [
        'label'       => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_text_color',
        'displayCond' => 'FIELD:tx_conn_base_bg_type:=:color',
        'config'      => [
            'type'    => 'color',
            'default' => '',
        ],
    ],
    'tx_conn_base_bg_image' => [
        'label'       => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_image',
        'displayCond' => 'FIELD:tx_conn_base_bg_type:=:image',
        'config'      => [
            'type'     => 'file',
            'allowed'  => 'jpg,jpeg,png,gif,webp',
            'maxitems' => 1,
        ],
    ],
    'tx_conn_base_bg_mode' => [
        'label'       => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_mode',
        'displayCond' => 'FIELD:tx_conn_base_bg_type:=:image',
        'config'      => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'default'    => 'cover',
            'items'      => [
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_mode.cover',    'value' => 'cover'],
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_mode.contain',  'value' => 'contain'],
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_mode.parallax', 'value' => 'parallax'],
            ],
        ],
    ],
]);

// Inject into the 'frames' palette that every CE type uses in its Appearance tab
ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'frames',
    'tx_conn_base_content_width, tx_conn_base_bg_type, tx_conn_base_bg_color, tx_conn_base_bg_text_color, tx_conn_base_bg_image, tx_conn_base_bg_mode',
    'after:frame_class'
);
