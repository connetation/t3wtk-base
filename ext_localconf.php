<?php

declare(strict_types=1);

/*
 * This file is part of the Connetation TYPO3 Web Toolkit - T3WTK.
 *
 * Copyright (c) Connetation Web Engineering GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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

defined('TYPO3') or die();

// Content element wizard entries are registered by EXT:conn_t3wtk_elements (optional package, required per project).

// Named RTE preset — selected per site via the connetation/t3wtk-base Set's page.tsconfig
// (RTE.default.preset = connBase) so other sites in the installation keep
// the core default preset.
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['connBase'] = 'EXT:conn_t3wtk_base/Configuration/RTE/Conn.yaml';

// Emoji picker/autocomplete editor-chrome CSS (grid, categories, tone
// selector, search balloon) — the plugin ships this separately from its JS,
// see Configuration/RTE/Conn.yaml and Configuration/JavaScriptModules.php.
$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'][] = 'EXT:conn_t3wtk_base/Resources/Public/Css/ckeditor5-emoji-editor.css';
