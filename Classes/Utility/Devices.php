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

namespace Connetation\T3wtkBase\Utility;

/**
 * THE device breakpoints for every per-device value in all conn/* packages —
 * content element fields (renderType connResponsiveRange), site settings
 * (type connResponsive) and the classes generated from them:
 *   Mobile < 768px, Tablet 768–1199px, Desktop ≥ 1200px.
 *
 * Backend labels/descriptions and generated Bootstrap classes
 * (ResponsiveValue, conn:responsiveClasses) read them from here.
 * Frontend counterparts — keep them in step when changing a value:
 *   SCSS: Resources/Private/Scss/theme/_devices.scss
 *   TS:   Resources/Private/JavaScript/devices.ts
 */
final class Devices
{
    /** Order of the stored value "desktop,tablet,mobile". */
    public const ALL = ['desktop', 'tablet', 'mobile'];

    /** Screen width (px) where Tablet starts — Bootstrap breakpoint "md". */
    public const TABLET_MIN = 768;

    /** Screen width (px) where Desktop starts — Bootstrap breakpoint "xl". */
    public const DESKTOP_MIN = 1200;

    public const TABLET_INFIX  = 'md';
    public const DESKTOP_INFIX = 'xl';

    /**
     * Screen-width range of a device in px (null = open end).
     *
     * @return array{min: ?int, max: ?int}
     */
    public static function range(string $device): array
    {
        return match ($device) {
            'desktop' => ['min' => self::DESKTOP_MIN, 'max' => null],
            'tablet'  => ['min' => self::TABLET_MIN, 'max' => self::DESKTOP_MIN - 1],
            default   => ['min' => null, 'max' => self::TABLET_MIN - 1],
        };
    }
}
