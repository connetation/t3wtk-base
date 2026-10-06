<?php

declare(strict_types=1);

return [
    'frontend' => [
        'connetation/t3wtk-base/branding-icons' => [
            'target' => \Connetation\T3wtkBase\Middleware\BrandingIconsMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
        'connetation/t3wtk-base/news-first-page-redirect' => [
            'target' => \Connetation\T3wtkBase\Middleware\NewsFirstPageRedirectMiddleware::class,
            'after' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
        ],
    ],
];
