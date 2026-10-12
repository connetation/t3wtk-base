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

namespace Connetation\T3wtkBase\Middleware;

use Connetation\T3wtkBase\Utility\ResourcePathUtility;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;

/**
 * Answers the icon URLs browsers, crawlers and link previews request on their own
 * (without reading the <head>) from the branding.* site settings:
 *
 *   /favicon.ico                     → branding.faviconIco (or branding.favicon if it's not SVG)
 *   /apple-touch-icon.png            → branding.appleTouchIcon
 *   /apple-touch-icon-precomposed.png→ branding.appleTouchIcon
 *   /site.webmanifest                → generated from branding.manifestIcon192/512
 *
 * Paths are relative to the site base. A real file with the same name in public/ wins,
 * because the web server serves it before TYPO3 is reached. Unset settings fall through
 * to the regular TYPO3 404.
 */
final class BrandingIconsMiddleware implements MiddlewareInterface
{
    public const MANIFEST_FILENAME = 'site.webmanifest';

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site || !in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $handler->handle($request);
        }

        $basePath = self::basePath($site);
        $path = $request->getUri()->getPath();
        if (!str_starts_with($path, $basePath)) {
            return $handler->handle($request);
        }

        $settings = $site->getSettings();
        $response = match (substr($path, strlen($basePath))) {
            'favicon.ico' => $this->fileResponse(self::faviconIco($settings)),
            'apple-touch-icon.png', 'apple-touch-icon-precomposed.png' => $this->fileResponse((string)$settings->get('branding.appleTouchIcon', '')),
            self::MANIFEST_FILENAME => $this->manifestResponse($site, $request->getAttribute('language'), $basePath),
            default => null,
        };

        return $response ?? $handler->handle($request);
    }

    /**
     * Site base path with trailing slash, e.g. "/" or "/de/".
     */
    public static function basePath(Site $site): string
    {
        return rtrim($site->getBase()->getPath(), '/') . '/';
    }

    /**
     * Setting used for /favicon.ico. SVG can't be served there — legacy clients expect ICO/PNG.
     */
    public static function faviconIco(SiteSettings $settings): string
    {
        $ico = (string)$settings->get('branding.faviconIco', '');
        if ($ico !== '') {
            return $ico;
        }
        $favicon = (string)$settings->get('branding.favicon', '');
        return ResourcePathUtility::mimeType($favicon) === 'image/svg+xml' ? '' : $favicon;
    }

    /**
     * @return list<array{src: string, sizes: string, type: string}>
     */
    public static function manifestIcons(SiteSettings $settings): array
    {
        $icons = [];
        foreach (['192x192' => 'branding.manifestIcon192', '512x512' => 'branding.manifestIcon512'] as $sizes => $key) {
            $src = ResourcePathUtility::webPath((string)$settings->get($key, ''));
            if ($src !== '') {
                $icons[] = ['src' => $src, 'sizes' => $sizes, 'type' => ResourcePathUtility::mimeType($src) ?: 'image/png'];
            }
        }
        return $icons;
    }

    private function fileResponse(string $path): ?ResponseInterface
    {
        if ($path === '') {
            return null;
        }

        if (ResourcePathUtility::isUrl($path)) {
            return $this->responseFactory->createResponse(302)->withHeader('Location', $path);
        }

        $file = ResourcePathUtility::localFile($path);
        if ($file === null) {
            return null;
        }

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', ResourcePathUtility::mimeType($file) ?: 'application/octet-stream')
            ->withHeader('Content-Length', (string)filesize($file))
            ->withHeader('Cache-Control', 'public, max-age=604800')
            ->withBody($this->streamFactory->createStreamFromFile($file));
    }

    private function manifestResponse(Site $site, mixed $language, string $basePath): ?ResponseInterface
    {
        $settings = $site->getSettings();
        $icons = self::manifestIcons($settings);
        if ($icons === []) {
            return null;
        }

        $name = $language instanceof SiteLanguage ? $language->getWebsiteTitle() : '';
        $name = $name ?: (string)($site->getConfiguration()['websiteTitle'] ?? '');
        $name = $name ?: (string)$settings->get('branding.logoAlt', '');
        $name = $name ?: $site->getBase()->getHost();

        $manifest = [
            'name' => $name,
            'short_name' => $name,
            'icons' => $icons,
            'start_url' => $basePath,
            'display' => 'browser',
        ];
        $color = (string)$settings->get('theme.light.bg', '');
        if ($color !== '') {
            $manifest['theme_color'] = $color;
            $manifest['background_color'] = $color;
        }

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/manifest+json; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=86400')
            ->withBody($this->streamFactory->createStream(
                (string)json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ));
    }
}
