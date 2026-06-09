<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// Register backend layouts
$backendLayouts = [
    ['pagets__conn_default', 'Default (1 column)'],
    ['pagets__conn_landingpage', 'Landing Page'],
];

foreach ($backendLayouts as [$value, $label]) {
    $GLOBALS['TCA']['pages']['columns']['backend_layout']['config']['items'][] = [
        'label' => $label,
        'value' => $value,
    ];
    $GLOBALS['TCA']['pages']['columns']['backend_layout_next_level']['config']['items'][] = [
        'label' => $label,
        'value' => $value,
    ];
}

// Add custom page fields
ExtensionManagementUtility::addTCAcolumns('pages', [
    'tx_conn_base_subtitle' => [
        'label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_subtitle',
        'config' => [
            'type' => 'input',
            'size' => 50,
            'max' => 255,
        ],
    ],
    'tx_conn_base_header_image' => [
        'label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_header_image',
        'config' => [
            'type' => 'file',
            'minitems' => 0,
            'maxitems' => 1,
            'allowed' => 'common-image-types',
            'appearance' => ['createNewRelationLinkTitle' => 'Add image'],
        ],
    ],
    'tx_conn_base_hide_breadcrumb' => [
        'label' => 'LLL:EXT:conn_base/Resources/Private/Language/locallang_be.xlf:pages.tx_conn_base_hide_breadcrumb',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'items' => [['label' => '', 'value' => 0]],
        ],
    ],
]);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;Connetation,tx_conn_base_subtitle,tx_conn_base_header_image,tx_conn_base_hide_breadcrumb',
    '',
    'after:title'
);
