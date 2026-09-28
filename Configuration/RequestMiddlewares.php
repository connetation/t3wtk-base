<?php

declare(strict_types=1);

return [
    'frontend' => [
        'connetation/t3kit-base/branding-icons' => [
            'target' => \Connetation\T3kitBase\Middleware\BrandingIconsMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
        'connetation/t3kit-base/news-first-page-redirect' => [
            'target' => \Connetation\T3kitBase\Middleware\NewsFirstPageRedirectMiddleware::class,
            'after' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
        ],
    ],
];
