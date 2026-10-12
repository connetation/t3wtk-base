<?php

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

return [
    'dependencies' => [
        'backend',
    ],
    'imports' => [
        '@typo3/conn-t3wtk-base/' => [
            'path' => 'EXT:conn_t3wtk_base/Resources/Public/JavaScript/',
        ],
        '@ckeditor/ckeditor5-core/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-core.js',
        '@ckeditor/ckeditor5-utils/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-utils.js',
        '@ckeditor/ckeditor5-typing/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-typing.js',
        '@ckeditor/ckeditor5-ui/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-ui.js',
        '@ckeditor/ckeditor5-icons/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-icons.js',
    ],
];
