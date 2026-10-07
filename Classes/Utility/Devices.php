<?php

declare(strict_types=1);

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
