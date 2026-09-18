<?php

return [
    'dependencies' => [
        'backend',
    ],
    'imports' => [
        '@typo3/conn-t3kit-base/' => [
            'path' => 'EXT:conn_t3kit_base/Resources/Public/JavaScript/',
        ],
        '@ckeditor/ckeditor5-core/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-core.js',
        '@ckeditor/ckeditor5-utils/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-utils.js',
        '@ckeditor/ckeditor5-typing/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-typing.js',
        '@ckeditor/ckeditor5-ui/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-ui.js',
        '@ckeditor/ckeditor5-icons/dist/index.js' => 'EXT:rte_ckeditor/Resources/Public/Contrib/@ckeditor/ckeditor5-icons.js',
    ],
];
