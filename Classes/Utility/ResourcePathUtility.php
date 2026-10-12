<?php

declare(strict_types=1);

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

namespace Connetation\T3wtkBase\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Resolves the path format used by path-type site settings (logos, favicons, …):
 * EXT: path, fileadmin path (with or without leading slash) or full URL.
 */
final class ResourcePathUtility
{
    private const MIME_TYPES = [
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
    ];

    public static function isUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//');
    }

    /**
     * Public URL for use in HTML attributes. Returns '' when an EXT: path can't be resolved.
     */
    public static function webPath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'EXT:')) {
            $absolute = GeneralUtility::getFileAbsFileName($path);
            return $absolute !== '' ? PathUtility::getAbsoluteWebPath($absolute) : '';
        }

        if (str_starts_with($path, '/') || self::isUrl($path)) {
            return $path;
        }

        // Editor-entered fileadmin-relative paths (e.g. "fileadmin/theme/icons/x.svg") have no
        // leading slash, so the browser resolves them against the current page's URL directory
        // instead of the site root — breaking on any route deeper than one path segment
        // (e.g. news detail pages). Force them absolute from the site root.
        return '/' . $path;
    }

    /**
     * Absolute filesystem path of a local file, or null for URLs and missing files.
     * Non-EXT paths are only resolved inside the public directory: ".." segments are
     * rejected instead of comparing realpath()s, because deployments commonly symlink
     * public/fileadmin to a shared directory outside the public path.
     */
    public static function localFile(string $path): ?string
    {
        if ($path === '' || self::isUrl($path)) {
            return null;
        }

        if (str_starts_with($path, 'EXT:')) {
            $absolute = GeneralUtility::getFileAbsFileName($path);
            return $absolute !== '' && is_file($absolute) ? $absolute : null;
        }

        $relative = ltrim($path, '/');
        if (in_array('..', explode('/', str_replace('\\', '/', $relative)), true)) {
            return null;
        }

        $absolute = Environment::getPublicPath() . '/' . $relative;
        return is_file($absolute) ? $absolute : null;
    }

    /**
     * MIME type derived from the file extension, '' when unknown.
     */
    public static function mimeType(string $path): string
    {
        $extension = strtolower(pathinfo((string)parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
        return self::MIME_TYPES[$extension] ?? '';
    }
}
