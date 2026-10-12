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

use Connetation\T3wtkBase\Utility\Devices;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Turns a per-device value ("desktop,tablet,mobile" — site setting type
 * connResponsive or a connResponsiveRange field) into mobile-first Bootstrap
 * classes. Breakpoint infixes come from Utility\Devices.
 *
 * An empty tablet/mobile value takes the next larger device's value; a class
 * is only added where a device differs from the one below it.
 *
 *   <conn:responsiveClasses value="4,6,12" pattern="col[bp]-[value]" />
 *       → "col-12 col-md-6 col-xl-4"
 *   <conn:responsiveClasses value="center,,start" pattern="text[bp]-[value]" />
 *       → "text-start text-md-center"
 *   <conn:responsiveClasses value="end,,center"
 *       map="{start: 'mx[bp]-0', center: 'mx[bp]-auto', end: 'ms[bp]-auto me[bp]-0'}" />
 *       → "mx-auto ms-md-auto me-md-0"
 *
 * [bp] becomes "" (mobile), "-md" (tablet) or "-xl" (desktop); [value] the
 * device's value (square brackets — Fluid would treat {…} inside a string as a
 * variable). "map" gives a pattern per value; values without a map entry use
 * "pattern" (or add no class when there is no pattern):
 *
 *   <conn:responsiveClasses value="auto,,12" map="{auto: 'col[bp]'}" pattern="col[bp]-[value]" />
 *       → "col-12 col-md"
 */
final class ResponsiveClassesViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'Per-device value "desktop,tablet,mobile"', true);
        $this->registerArgument('pattern', 'string', 'Class pattern with [bp] and [value]', false, '');
        $this->registerArgument('map', 'array', 'Class pattern per value (values without an entry use "pattern")', false, []);
    }

    public function render(): string
    {
        $parts   = array_map('trim', explode(',', (string)$this->arguments['value']));
        $desktop = $parts[0] ?? '';
        $tablet  = ($parts[1] ?? '') !== '' ? $parts[1] : $desktop;
        $mobile  = ($parts[2] ?? '') !== '' ? $parts[2] : $tablet;

        $classes = [$this->classFor($mobile, '')];
        if ($tablet !== $mobile) {
            $classes[] = $this->classFor($tablet, '-' . Devices::TABLET_INFIX);
        }
        if ($desktop !== $tablet) {
            $classes[] = $this->classFor($desktop, '-' . Devices::DESKTOP_INFIX);
        }

        return implode(' ', array_filter($classes));
    }

    private function classFor(string $value, string $breakpoint): string
    {
        if ($value === '') {
            return '';
        }
        $map     = (array)$this->arguments['map'];
        $pattern = (string)($map[$value] ?? $this->arguments['pattern']);

        return str_replace(['[bp]', '[value]'], [$breakpoint, $value], $pattern);
    }
}
