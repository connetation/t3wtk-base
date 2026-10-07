<?php

declare(strict_types=1);

namespace Connetation\T3wtkBase\Utility;

/**
 * Per-device values from a renderType "connResponsiveRange" field, stored as
 * "desktop,tablet,mobile" (e.g. "3,,1"). A single value is desktop only;
 * former select keywords can be mapped via $legacy so switching an existing
 * select field to the responsive field needs no data migration.
 *
 * Devices and their breakpoints: see Devices (the single source).
 */
final class ResponsiveValue
{
    /**
     * Explicitly set values; tablet/mobile are null when left empty (or invalid).
     *
     * @param array<string, int> $legacy former keyword values, e.g. ['large' => 70]
     * @return array{desktop: int, tablet: ?int, mobile: ?int}
     */
    public static function parse(mixed $raw, int $min, int $max, int $default, array $legacy = []): array
    {
        $parts = \is_string($raw) ? array_map('trim', explode(',', $raw)) : [$raw];

        return [
            'desktop' => self::single($parts[0] ?? null, $min, $max, $legacy) ?? $default,
            'tablet'  => self::single($parts[1] ?? null, $min, $max, $legacy),
            'mobile'  => self::single($parts[2] ?? null, $min, $max, $legacy),
        ];
    }

    /**
     * Empty tablet/mobile take the value of the next larger device.
     *
     * @param array{desktop: int, tablet: ?int, mobile: ?int} $values
     * @return array{desktop: int, tablet: int, mobile: int}
     */
    public static function inherited(array $values): array
    {
        $tablet = $values['tablet'] ?? $values['desktop'];

        return ['desktop' => $values['desktop'], 'tablet' => $tablet, 'mobile' => $values['mobile'] ?? $tablet];
    }

    /**
     * Bootstrap row-cols-* classes for a .row of cards. Empty = automatic:
     * tablet at most 2 columns, mobile 1 (the layout these elements had
     * before the columns became configurable per device).
     *
     * $autoTablet / $autoMobile override that automatic value for an element
     * whose items are small (e.g. thumbnails).
     *
     * @param array{desktop: int, tablet: ?int, mobile: ?int} $values
     */
    public static function rowColsClass(array $values, ?int $autoTablet = null, ?int $autoMobile = null): string
    {
        $desktop = $values['desktop'];
        $tablet  = $values['tablet'] ?? $autoTablet ?? min($desktop, 2);
        $mobile  = $values['mobile'] ?? $autoMobile ?? 1;

        return sprintf(
            'row-cols-%d row-cols-%s-%d row-cols-%s-%d',
            $mobile,
            Devices::TABLET_INFIX,
            $tablet,
            Devices::DESKTOP_INFIX,
            $desktop
        );
    }

    private static function single(mixed $value, int $min, int $max, array $legacy): ?int
    {
        if (\is_string($value) && isset($legacy[$value])) {
            return $legacy[$value];
        }
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        $number = (int)$value;

        return ($number >= $min && $number <= $max) ? $number : null;
    }
}
