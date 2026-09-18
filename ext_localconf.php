<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Content element wizard entries are registered by EXT:conn_t3kit_elements (optional package, required per project).

// Named RTE preset — selected per site via the conn/t3kit-base Set's page.tsconfig
// (RTE.default.preset = connBase) so other sites in the installation keep
// the core default preset.
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['connBase'] = 'EXT:conn_t3kit_base/Configuration/RTE/Conn.yaml';

// Emoji picker/autocomplete editor-chrome CSS (grid, categories, tone
// selector, search balloon) — the plugin ships this separately from its JS,
// see Configuration/RTE/Conn.yaml and Configuration/JavaScriptModules.php.
$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'][] = 'EXT:conn_t3kit_base/Resources/Public/Css/ckeditor5-emoji-editor.css';
