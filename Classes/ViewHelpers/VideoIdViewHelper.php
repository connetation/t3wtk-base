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

namespace Connetation\T3wtkBase\ViewHelpers;

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
