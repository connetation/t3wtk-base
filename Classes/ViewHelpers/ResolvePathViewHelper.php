<?php

declare(strict_types=1);

namespace Connetation\Base\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class ResolvePathViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('path', 'string', 'EXT: path, fileadmin path, or full URL', true);
    }

    public function render(): string
    {
        $path = (string)$this->arguments['path'];

        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'EXT:')) {
            $absolute = GeneralUtility::getFileAbsFileName($path);
            return $absolute !== '' ? PathUtility::getAbsoluteWebPath($absolute) : '';
        }

        if (str_starts_with($path, '/') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        // Editor-entered fileadmin-relative paths (e.g. "fileadmin/theme/icons/x.svg") have no
        // leading slash, so the browser resolves them against the current page's URL directory
        // instead of the site root — breaking on any route deeper than one path segment
        // (e.g. news detail pages). Force them absolute from the site root.
        return '/' . $path;
    }
}
