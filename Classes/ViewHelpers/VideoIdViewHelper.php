<?php

declare(strict_types=1);

namespace Connetation\T3kitBase\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Extracts the video ID from a YouTube or Vimeo public URL.
 *
 * Usage: <conn:videoId url="{file.publicUrl}" />
 */
class VideoIdViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('url', 'string', 'YouTube or Vimeo public URL', true);
    }

    public function render(): string
    {
        $url = (string)($this->arguments['url'] ?? '');

        // YouTube: watch?v=ID, youtu.be/ID, /embed/ID, /shorts/ID, /live/ID
        if (preg_match(
            '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts|live)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i',
            $url,
            $m
        )) {
            return $m[1];
        }

        // Vimeo: vimeo.com/ID
        if (preg_match('%vimeo\.com/(\d+)%i', $url, $m)) {
            return $m[1];
        }

        return '';
    }
}
