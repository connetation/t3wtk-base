<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Connetation Sitepackage',
    'description' => 'TYPO3 LTS 14 sitepackage based on Bootstrap 5 with light/dark theme and project dashboard',
    'category' => 'templates',
    'author' => 'Connetation',
    'author_email' => 'i.sapina@connetation.at',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3'               => '14.0.0-14.99.99',
            'fluid_styled_content' => '14.0.0-14.99.99'
        ],
    ],
];
