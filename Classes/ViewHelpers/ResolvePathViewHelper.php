<?php

declare(strict_types=1);

namespace Connetation\T3kitBase\ViewHelpers;

use Connetation\T3kitBase\Utility\ResourcePathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class ResolvePathViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('path', 'string', 'EXT: path, fileadmin path, or full URL', true);
    }

    public function render(): string
    {
        return ResourcePathUtility::webPath((string)$this->arguments['path']);
    }
}
