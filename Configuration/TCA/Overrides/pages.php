<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// Backend layouts are defined once via page TSconfig (Set page.tsconfig →
// TsConfig/Page/BackendLayouts.tsconfig); TYPO3 merges them into the
// backend_layout selects automatically — no static TCA items needed here.

// Add custom page fields
ExtensionManagementUtility::addTCAcolumns('pages', [
    'tx_conn_base_hide_breadcrumb' => [
        'label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_hide_breadcrumb',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'items' => [['label' => '', 'value' => 0]],
        ],
    ],
    // Only relevant for the conn_topcontent backend layout: where the
    // breadcrumb renders relative to the Top Content column.
    'tx_conn_base_breadcrumb_position' => [
        'label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_breadcrumb_position',
        'displayCond' => 'FIELD:backend_layout:=:pagets__conn_topcontent',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'default' => '',
            'items' => [
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_breadcrumb_position.under', 'value' => ''],
                ['label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_breadcrumb_position.top', 'value' => 'top'],
            ],
        ],
    ],
    // Per-page override of --conn-bg / --bs-body-bg (theme/_light.scss, theme/_dark.scss).
    // Rendered by Partials/Page/ThemeVars.html for both light and dark mode.
    'tx_conn_base_bg_color' => [
        'label'  => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_bg_color',
        'config' => [
            'type'    => 'color',
            'opacity' => true,
            'default' => '',
        ],
    ],
    // Per-page override of --conn-text / --bs-body-color (theme/_light.scss, theme/_dark.scss).
    // Rendered by Partials/Page/ThemeVars.html for both light and dark mode.
    'tx_conn_base_text_color' => [
        'label'  => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_text_color',
        'config' => [
            'type'    => 'color',
            'opacity' => true,
            'default' => '',
        ],
    ],
    // Per-page override of --conn-heading / --bs-heading-color (theme/_light.scss, theme/_dark.scss).
    // Rendered by Partials/Page/ThemeVars.html for both light and dark mode.
    'tx_conn_base_heading_color' => [
        'label'  => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_heading_color',
        'config' => [
            'type'    => 'color',
            'opacity' => true,
            'default' => '',
        ],
    ],
]);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;Connetation,tx_conn_base_hide_breadcrumb,tx_conn_base_breadcrumb_position,tx_conn_base_bg_color,tx_conn_base_text_color,tx_conn_base_heading_color',
    '',
    'after:title'
);
