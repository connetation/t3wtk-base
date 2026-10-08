<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Connetation TYPO3 Web Toolkit: Base',
    'description' => 'TYPO3 LTS 14 base extension with Bootstrap 5, light/dark theme, templates and default configuration',
    'category' => 'templates',
    'author' => 'Connetation Web Engineering GmbH',
    'author_email' => 'office@connetation.at',
    'state' => 'stable',
    'version' => '3.1.0',
    'constraints' => [
        'depends' => [
            'typo3'                => '14.0.0-14.99.99',
            'fluid_styled_content' => '14.0.0-14.99.99',
        ],
    ],
];
