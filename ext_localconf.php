<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Content element wizard entries are registered by EXT:conn_elements (optional package, required per project).

// Named RTE preset — selected per site via the conn/base Set's page.tsconfig
// (RTE.default.preset = connBase) so other sites in the installation keep
// the core default preset.
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['connBase'] = 'EXT:conn_base/Configuration/RTE/Default.yaml';

// The News List/Card plugin's "Alignment" sheet is added via the
// AddNewsListAppearanceFields event listener (AfterFlexFormDataStructureParsedEvent),
// not addPiFlexFormValue() — that API is deprecated in TYPO3 v14 and no longer
// merges sheets, it just overwrites the ds pointer outright. Since
// georgringer/news sets its own ds in Configuration/TCA/Overrides/tt_content.php,
// which always runs after every extension's ext_localconf.php, its assignment
// unconditionally won and this sheet never appeared.
