<?php

declare(strict_types=1);

namespace Connetation\Sitepackage\ViewHelpers;

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

        return $path;
    }
}
