<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// Content element registrations live in EXT:conn_elements (optional package, required per project).
// Add conn_base CE overrides here if needed.

// ─── Section background – global field for ALL content element types ─────
ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'tx_conn_base_content_width' => [
        'label'       => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:content_width',
        'displayCond' => 'FIELD:frame_class:=:none',
        'config'      => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'default'    => '',
            'items'      => [
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:content_width.default',   'value' => ''],
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:content_width.full',      'value' => 'full'],
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:content_width.fullbleed', 'value' => 'fullbleed'],
            ],
        ],
    ],
    'tx_conn_base_bg_type' => [
        'label'  => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_type',
        'config' => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            // Forces a full record reload on change, same as FlexForm's
            // <onChange>reload</onChange> — needed so the color/image field
            // group swapped in by displayCond re-renders cleanly instead of
            // just toggling visibility in place.
            'onChange'   => 'reload',
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
            'opacity' => true,
            'default' => '',
        ],
    ],
    'tx_conn_base_bg_text_color' => [
        'label'       => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:bg_text_color',
        'displayCond' => 'FIELD:tx_conn_base_bg_type:=:color',
        'config'      => [
            'type'    => 'color',
            'opacity' => true,
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

// The 'frames' palette showitem (grouped rows, incl. this content_width field
// and any optional conn_elements field like tx_conn_elements_text_animation)
// is assembled from scratch in EventListener\ReorderFramesPalette, which runs
// on AfterTcaCompilationEvent — after every extension (including optional
// ones) has already inserted its own fields via addFieldsToPalette. Doing the
// grouping there, instead of here, avoids depending on extension load order:
// whichever extension's addFieldsToPalette('after:frame_class', ...) call
// runs last would otherwise land closest to frame_class, silently breaking
// the row grouping.

// ─── Space Before/After — extend core's to match --conn-space-* ──
// _content-frames.scss 
foreach (['space_before_class', 'space_after_class'] as $spaceField) {
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        $spaceField,
        ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:space_class.extra_extra_small', 'value' => 'extra-extra-small'],
        'extra-small',
        'before'
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        $spaceField,
        ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:space_class.extra_extra_large', 'value' => 'extra-extra-large'],
        'extra-large',
        'after'
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        $spaceField,
        ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:space_class.none', 'value' => 'none'],
        'extra-extra-small',
        'before'
    );
}

// ─── Header/Subheader — relabel header_layout, add class overrides ────────
// Core's own labels are "Layout 1".."Layout 5", which don't tell editors
// which heading tag they're actually choosing — relabel to the real tag
// names. Values are unchanged (0=default, 1-5=h1-h5, 100=hidden), so this
// is purely a label override, not a data migration.
$GLOBALS['TCA']['tt_content']['columns']['header_layout']['config']['items'] = [
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_layout.default', 'value' => '0'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_layout.h1', 'value' => '1'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_layout.h2', 'value' => '2'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_layout.h3', 'value' => '3'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_layout.h4', 'value' => '4'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_layout.h5', 'value' => '5'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_layout.hidden', 'value' => '100'],
];

// header_class/subheader_class let an editor pick the heading TAG (via
// header_layout, for correct document outline/SEO) independently of its
// visual SIZE (a Bootstrap .h1-.h5 utility class) — e.g. an <h3> styled
// like an .h2. Rendered in HeaderSwitch.html.
$headerClassItems = [
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_class.none', 'value' => ''],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_class.h1', 'value' => 'h1'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_class.h2', 'value' => 'h2'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_class.h3', 'value' => 'h3'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_class.h4', 'value' => 'h4'],
    ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_class.h5', 'value' => 'h5'],
];

// Subheader previously had no layout choice at all — HeaderSwitch.html always
// rendered it as a plain <p>, unlike header (switchable h1-h6). That was the
// one inconsistency in the reference header/subheader implementation itself:
// every OTHER header/subheader pair in the project (Card Group, Pricing,
// Slider, ...) must always render a real heading tag, so this one should too.
// Reuses header_layout's item set/labels (Default/H1-H5/Hidden) for parity.
ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'tx_conn_base_subheader_layout' => [
        'label'  => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:subheader_layout',
        'config' => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'default'    => '0',
            'items'      => $GLOBALS['TCA']['tt_content']['columns']['header_layout']['config']['items'],
        ],
    ],
    'tx_conn_base_header_class' => [
        'label'  => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:header_class',
        'config' => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'default'    => '',
            'items'      => $headerClassItems,
        ],
    ],
    'tx_conn_base_subheader_class' => [
        'label'  => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:subheader_class',
        'config' => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'default'    => '',
            'items'      => $headerClassItems,
        ],
    ],
]);

// header_class fits both 'header' and 'headers' (every CE has a header);
// subheader_layout/subheader_class only make sense in 'headers' (the only
// palette with subheader).
ExtensionManagementUtility::addFieldsToPalette('tt_content', 'header', 'tx_conn_base_header_class', 'after:header_layout');
ExtensionManagementUtility::addFieldsToPalette('tt_content', 'headers', 'tx_conn_base_header_class', 'after:header_layout');
ExtensionManagementUtility::addFieldsToPalette('tt_content', 'headers', 'tx_conn_base_subheader_layout, tx_conn_base_subheader_class', 'after:subheader');
