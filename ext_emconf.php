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

$EM_CONF[$_EXTKEY] = [
    'title' => 'Connetation TYPO3 Web Toolkit: Base',
    'description' => 'TYPO3 LTS 14 base extension with Bootstrap 5, light/dark theme, templates and default configuration',
    'category' => 'templates',
    'author' => 'Connetation Web Engineering GmbH',
    'author_email' => 'office@connetation.at',
    'state' => 'stable',
    'version' => '3.1.2',
    'constraints' => [
        'depends' => [
            'typo3'                => '14.0.0-14.99.99',
            'fluid_styled_content' => '14.0.0-14.99.99',
        ],
    ],
];
