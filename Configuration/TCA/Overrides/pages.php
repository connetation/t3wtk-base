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
]);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;Connetation,tx_conn_base_hide_breadcrumb,tx_conn_base_bg_color',
    '',
    'after:title'
);
