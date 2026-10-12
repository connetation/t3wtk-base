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

namespace Connetation\T3wtkBase\Upgrades;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * Footer grid columns: the per-breakpoint site settings (13 per column —
 * size.base/sm/md/lg/xl/xxl, mobile/desktop align, justify, valign) became
 * 3 per-device settings of type connResponsive (width, align, valign;
 * "desktop,tablet,mobile"). "align" now covers both the former text
 * alignment and position in row. Converts each site's settings.yaml.
 *
 * Mapping: Mobile = the "base" width, Tablet = the width at 768px (md),
 * Desktop = the width at 1200px (xl); keys missing in settings.yaml use the
 * former defaults. Alignment classes → start/center/end.
 */
#[UpgradeWizard('connT3wtkBaseFooterResponsiveSettings')]
final readonly class FooterResponsiveSettingsUpdate implements UpgradeWizardInterface
{
    private const COLUMNS = [1, 2, 3, 4];

    private const BREAKPOINTS = ['base', 'sm', 'md', 'lg', 'xl', 'xxl'];

    private const LEGACY_SUFFIXES = [
        'size.base', 'size.sm', 'size.md', 'size.lg', 'size.xl', 'size.xxl',
        'mobilealign', 'desktopalign', 'mobilejustify', 'desktopjustify', 'mobilevalign', 'desktopvalign',
    ];

    /**
     * Bottom bar: former slot name → [column number, its former fixed alignment from
     * 768px up (template classes text-md-*)]. Slots are columns 1-3 now.
     */
    private const BAR_SLOTS = ['left' => [1, 'start'], 'center' => [2, 'center'], 'right' => [3, 'end']];

    /** Bottom bar alignment defaults per column (settings.definitions.yaml) — values equal to them aren't written. */
    private const BAR_ALIGN_DEFAULTS = [1 => 'start', 2 => 'center,,start', 3 => 'end,,start'];

    /** New defaults (settings.definitions.yaml) — values equal to them aren't written. */
    private const NEW_DEFAULTS = ['width' => '4,6,12', 'align' => 'start', 'valign' => 'start'];

    public function __construct(
        private SiteFinder $siteFinder,
        private SiteWriter $siteWriter,
    ) {}

    public function getTitle(): string
    {
        return 'EXT:conn_t3wtk_base: Footer column + bottom bar settings → per-device settings (Desktop / Tablet / Mobile)';
    }

    public function getDescription(): string
    {
        return 'The footer grid column settings (width per breakpoint, mobile/desktop text alignment, position in '
            . 'row and vertical position) became Desktop / Tablet / Mobile settings: width, alignment (text and '
            . 'position together) and vertical position. Converts the values in every site\'s settings.yaml and '
            . 'removes the old keys. The bottom bar slots left / center / right become columns 1-3 (content, '
            . 'width, alignment); their mobile-only alignments become one per-device alignment (desktop keeps '
            . 'the former left / center / right).';
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function updateNecessary(): bool
    {
        foreach ($this->siteIdentifiers() as $identifier) {
            if ($this->hasLegacyKeys($this->loadSettings($identifier) ?? [])) {
                return true;
            }
        }

        return false;
    }

    public function executeUpdate(): bool
    {
        foreach ($this->siteIdentifiers() as $identifier) {
            $settings = $this->loadSettings($identifier);
            if ($settings === null || !$this->hasLegacyKeys($settings)) {
                continue;
            }
            $this->siteWriter->writeSettings($identifier, $this->migrate($settings));
        }

        return true;
    }

    /**
     * @param array<string, mixed> $settings flat "footer.col1.size.md" => value
     * @return array<string, mixed>
     */
    public function migrate(array $settings): array
    {
        foreach (self::COLUMNS as $column) {
            $prefix = 'footer.col' . $column . '.';
            if (!$this->columnHasLegacyKeys($settings, $prefix)) {
                continue;
            }
            $legacy = fn (string $suffix, string $default): string => array_key_exists($prefix . $suffix, $settings)
                ? trim((string)$settings[$prefix . $suffix])
                : $default;

            // Width: the value in effect at a breakpoint = last non-empty one up to it.
            $legacyWidthDefaults = ['base' => '12', 'sm' => '', 'md' => '6', 'lg' => $column === 4 ? '3' : '4', 'xl' => '', 'xxl' => ''];
            $widthAt = function (string $breakpoint) use ($legacy, $legacyWidthDefaults): string {
                $width = '';
                foreach (self::BREAKPOINTS as $current) {
                    $value = $legacy('size.' . $current, $legacyWidthDefaults[$current]);
                    $width = $value !== '' ? $value : $width;
                    if ($current === $breakpoint) {
                        break;
                    }
                }
                return $width !== '' ? $width : '12';
            };
            $new = [
                'width'   => $this->compact($widthAt('xl'), $widthAt('md'), $widthAt('base')),
                'align'   => $this->compact(
                    $this->position($legacy('desktopalign', 'text-md-start')),
                    $this->position($legacy('desktopalign', 'text-md-start')),
                    $this->position($legacy('mobilealign', 'text-start'))
                ),
                // Position in row is part of "align" now (one setting aligns the
                // content and, when narrower than the row, the column itself).
                // Former desktop "None" added no class, so the mobile class stayed
                // in effect on larger screens too — keep what rendered.
                'valign'  => $this->compact(
                    $this->position($legacy('desktopvalign', '') ?: $legacy('mobilevalign', '')),
                    $this->position($legacy('desktopvalign', '') ?: $legacy('mobilevalign', '')),
                    $this->position($legacy('mobilevalign', ''))
                ),
            ];

            foreach (self::LEGACY_SUFFIXES as $suffix) {
                unset($settings[$prefix . $suffix]);
            }
            foreach ($new as $name => $value) {
                $default = $name === 'width' && $column === 4 ? '3,6,12' : self::NEW_DEFAULTS[$name];
                if ($value !== $default) {
                    $settings[$prefix . $name] = $value;
                }
            }
        }

        // Bottom bar: slots left / center / right are columns 1-3 now
        // (footer.bar.colN.content / .align / .width).
        foreach (self::BAR_SLOTS as $slot => [$column, $fixed]) {
            $prefix = 'footer.bar.col' . $column . '.';
            $renames = [
                'footer.bar.' . $slot          => $prefix . 'content',
                // interim per-device keys (pre-release)
                'footer.bar.' . $slot . 'Align' => $prefix . 'align',
                'footer.bar.' . $slot . 'Width' => $prefix . 'width',
            ];
            foreach ($renames as $old => $new) {
                if (array_key_exists($old, $settings)) {
                    $settings[$new] ??= $settings[$old];
                    unset($settings[$old]);
                }
            }
            // Only the mobile alignment was a setting (from 768px up each slot was
            // fixed left / center / right) → one per-device alignment.
            $legacyKey = 'footer.bar.' . $slot . 'MobileAlign';
            if (array_key_exists($legacyKey, $settings)) {
                $value = $this->compact($fixed, $fixed, $this->position(trim((string)$settings[$legacyKey])));
                unset($settings[$legacyKey]);
                if (!array_key_exists($prefix . 'align', $settings) && $value !== self::BAR_ALIGN_DEFAULTS[$column]) {
                    $settings[$prefix . 'align'] = $value;
                }
            }
        }

        return $settings;
    }

    /** "text-md-center" / "mx-auto" / "justify-content-md-end" / "" → start | center | end */
    private function position(string $class): string
    {
        return match (true) {
            str_contains($class, 'center'), str_starts_with($class, 'mx-') => 'center',
            str_ends_with($class, '-end'), str_starts_with($class, 'ms-') => 'end',
            default => 'start',
        };
    }

    /** Shortest "desktop,tablet,mobile" — a value equal to the larger device's is left empty. */
    private function compact(string $desktop, string $tablet, string $mobile): string
    {
        $parts = [$desktop, $tablet === $desktop ? '' : $tablet, $mobile === $tablet ? '' : $mobile];
        while (\count($parts) > 1 && end($parts) === '') {
            array_pop($parts);
        }

        return implode(',', $parts);
    }

    private function hasLegacyKeys(array $settings): bool
    {
        foreach (array_keys(self::BAR_SLOTS) as $slot) {
            foreach (['', 'MobileAlign', 'Align', 'Width'] as $suffix) {
                if (array_key_exists('footer.bar.' . $slot . $suffix, $settings)) {
                    return true;
                }
            }
        }
        foreach (self::COLUMNS as $column) {
            if ($this->columnHasLegacyKeys($settings, 'footer.col' . $column . '.')) {
                return true;
            }
        }

        return false;
    }

    private function columnHasLegacyKeys(array $settings, string $prefix): bool
    {
        foreach (self::LEGACY_SUFFIXES as $suffix) {
            if (array_key_exists($prefix . $suffix, $settings)) {
                return true;
            }
        }

        return false;
    }

    /** Raw settings.yaml (no placeholder processing), flattened to "a.b.c" keys. */
    private function loadSettings(string $identifier): ?array
    {
        $file = Environment::getConfigPath() . '/sites/' . $identifier . '/settings.yaml';
        if (!is_file($file)) {
            return null;
        }
        $settings = Yaml::parseFile($file);

        return \is_array($settings) ? $this->flatten($settings) : null;
    }

    /** Nested maps → "a.b.c" keys; list values (e.g. stringlist settings) stay as they are. */
    private function flatten(array $settings, string $prefix = ''): array
    {
        $flat = [];
        foreach ($settings as $key => $value) {
            if (\is_array($value) && $value !== [] && !array_is_list($value)) {
                $flat += $this->flatten($value, $prefix . $key . '.');
            } else {
                $flat[$prefix . $key] = $value;
            }
        }

        return $flat;
    }

    /** @return list<string> */
    private function siteIdentifiers(): array
    {
        return array_keys($this->siteFinder->getAllSites(false));
    }
}
