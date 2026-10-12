<?php

/*
 * This file is part of the Connetation TYPO3 Web Toolkit - T3WTK.
 *
 * Copyright (c) Connetation Web Engineering GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
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
    'description' => 'Design system and foundation of the Connetation TYPO3 Web Toolkit (T3WTK) for TYPO3 14 LTS: page templates, navigation, footer, light/dark mode and 250+ site settings. Themeable without writing code. Built on Site Sets and Bootstrap 5.',
    'category' => 'templates',
    'author' => 'Connetation Web Engineering GmbH',
    'author_email' => 'office@connetation.at',
    'state' => 'stable',
    'version' => '3.1.4',
    'constraints' => [
        'depends' => [
            'typo3'                => '14.0.0-14.99.99',
            'fluid_styled_content' => '14.0.0-14.99.99',
        ],
    ],
];
