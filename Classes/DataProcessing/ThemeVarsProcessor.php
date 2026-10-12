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

namespace Connetation\T3wtkBase\DataProcessing;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Resolves site settings (plus the per-page tx_conn_t3wtk_base_*_color fields)
 * into the complete <style> body text for Partials/Page/ThemeVars.html —
 * that template has no CSS syntax of its own, it only prints {themeVars.css}
 * if it's non-empty.
 */
final class ThemeVarsProcessor implements DataProcessorInterface
{
    private const LIGHT = [
        'theme.light.accent' => ['--conn-accent', '--bs-primary'],
        'theme.light.accentHover' => ['--conn-accent-hover'],
        'theme.accentSecondary' => ['--conn-accent-secondary'],
        'theme.light.bg' => ['--conn-bg', '--bs-body-bg'],
        'theme.light.surface' => ['--conn-surface', '--conn-surface-alt', '--conn-surface-raised'],
        'theme.light.navBg' => ['--conn-nav-bg'],
        'theme.light.navText' => ['--conn-nav-text'],
        'theme.light.footerBg' => ['--conn-footer-bg'],
        'theme.light.footerText' => ['--conn-footer-text'],
        'theme.light.footerLink' => ['--conn-footer-link'],
        'theme.light.footerLinkHover' => ['--conn-footer-link-hover'],
        'theme.light.footerLinkHoverUnderline' => ['--conn-footer-link-hover-underline'],
        'footer.fontSizeScale' => ['--conn-footer-font-size-scale'],
        'theme.light.text' => ['--conn-text', '--bs-body-color'],
        'theme.light.heading' => ['--conn-heading', '--bs-heading-color'],
        'theme.light.emphasis' => ['--conn-emphasis', '--bs-emphasis-color'],
        'theme.light.textMuted' => ['--conn-text-muted', '--bs-secondary-color'],
        'theme.light.eyebrow' => ['--conn-eyebrow-color'],
        'theme.light.link' => ['--conn-link', '--bs-link-color'],
        'theme.light.linkHover.text' => ['--conn-link-hover', '--bs-link-hover-color'],
        'theme.light.linkHover.underline' => ['--conn-link-hover-underline'],
        'theme.light.border' => ['--conn-border'],
        'theme.light.borderStrong' => ['--conn-border-strong'],
        'theme.light.accentSoft' => ['--conn-accent-soft'],
        'theme.light.accentGlow' => ['--conn-accent-glow'],
        'theme.light.btnBg' => ['--conn-btn-bg'],
        'theme.light.btnBgHover' => ['--conn-btn-bg-hover'],
        'theme.light.btnColor' => ['--conn-btn-color'],
        'theme.shadowHover.color' => ['--conn-sh-color'],
        'theme.shadowHover.offsetX' => ['--conn-sh-x'],
        'theme.shadowHover.offsetY' => ['--conn-sh-y'],
        'theme.shadowHover.blur' => ['--conn-sh-blur'],
        'theme.shadowHover.spread' => ['--conn-sh-spread'],
    ];

    private const DARK = [
        'theme.dark.accent' => ['--conn-accent', '--bs-primary'],
        'theme.dark.accentHover' => ['--conn-accent-hover'],
        'theme.accentSecondary' => ['--conn-accent-secondary'],
        'theme.dark.bg' => ['--conn-bg', '--bs-body-bg'],
        'theme.dark.surface' => ['--conn-surface', '--conn-surface-alt', '--conn-surface-raised'],
        'theme.dark.navBg' => ['--conn-nav-bg'],
        'theme.dark.navText' => ['--conn-nav-text'],
        'theme.dark.footerBg' => ['--conn-footer-bg'],
        'theme.dark.footerText' => ['--conn-footer-text'],
        'theme.dark.footerLink' => ['--conn-footer-link'],
        'theme.dark.footerLinkHover' => ['--conn-footer-link-hover'],
        'theme.dark.footerLinkHoverUnderline' => ['--conn-footer-link-hover-underline'],
        'footer.fontSizeScale' => ['--conn-footer-font-size-scale'],
        'theme.dark.text' => ['--conn-text', '--bs-body-color'],
        'theme.dark.heading' => ['--conn-heading', '--bs-heading-color'],
        'theme.dark.emphasis' => ['--conn-emphasis', '--bs-emphasis-color'],
        'theme.dark.textMuted' => ['--conn-text-muted', '--bs-secondary-color'],
        'theme.dark.eyebrow' => ['--conn-eyebrow-color'],
        'theme.dark.link' => ['--conn-link', '--bs-link-color'],
        'theme.dark.linkHover.text' => ['--conn-link-hover', '--bs-link-hover-color'],
        'theme.dark.linkHover.underline' => ['--conn-link-hover-underline'],
        'theme.dark.border' => ['--conn-border'],
        'theme.dark.borderStrong' => ['--conn-border-strong'],
        'theme.dark.accentSoft' => ['--conn-accent-soft'],
        'theme.dark.accentGlow' => ['--conn-accent-glow'],
        'theme.dark.btnBg' => ['--conn-btn-bg'],
        'theme.dark.btnBgHover' => ['--conn-btn-bg-hover'],
        'theme.dark.btnColor' => ['--conn-btn-color'],
        // Mode-independent settings: the light rule doesn't match in dark mode
        'theme.shadowHover.color' => ['--conn-sh-color'],
        'theme.shadowHover.offsetX' => ['--conn-sh-x'],
        'theme.shadowHover.offsetY' => ['--conn-sh-y'],
        'theme.shadowHover.blur' => ['--conn-sh-blur'],
        'theme.shadowHover.spread' => ['--conn-sh-spread'],
    ];

    // Bootstrap button variants without a conn design of their own. Their
    // theme.{light,dark}.btn<Variant>Bg / btn<Variant>Color settings only emit
    // override rules when set — otherwise Bootstrap's compiled defaults apply.
    private const BUTTON_VARIANTS = ['success', 'danger', 'warning', 'info', 'light', 'dark'];

    // Site setting theme.radius => --conn-radius-scale, the multiplier of every
    // --conn-radius* / --bs-border-radius* token (Scss/theme/_tokens.scss).
    // "default" (scale 1) emits nothing — the tokens' var() fallback already is 1.
    private const RADIUS_SCALES = [
        'none' => '0',
        'small' => '0.5',
        'large' => '1.5',
        'xlarge' => '2',
    ];

    // Per-page tx_conn_t3wtk_base_*_color fields (pages table, not site settings)
    private const PAGE_OVERRIDE = [
        'tx_conn_t3wtk_base_bg_color' => ['--conn-bg', 'background-color'],
        'tx_conn_t3wtk_base_text_color' => ['--conn-text', '--bs-body-color', 'color'],
        'tx_conn_t3wtk_base_heading_color' => ['--conn-heading', '--bs-heading-color'],
    ];

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $targetVar = $cObj->stdWrapValue('as', $processorConfiguration, 'themeVars');
        $site = $cObj->getRequest()->getAttribute('site');
        $settings = $site instanceof Site ? $site->getSettings() : null;

        $light = $this->resolveSettings($settings, self::LIGHT);
        $dark = $this->resolveSettings($settings, self::DARK);
        $pageMain = $this->resolveRecord($cObj->data, self::PAGE_OVERRIDE);

        $processedData[$targetVar] = [
            'css' => $this->buildRadiusCss($settings)
                . $this->buildFontScaleCss($settings)
                . $this->buildNavbarShrinkCss($settings)
                . $this->buildCss($light, $dark, $pageMain)
                // Light rules apply in both modes; dark rules win in dark mode
                // (same specificity, emitted later).
                . $this->buildButtonCss(':root', $settings, 'light')
                . $this->buildButtonCss('[data-bs-theme="dark"]', $settings, 'dark'),
        ];

        return $processedData;
    }

    /**
     * Builds the complete <style> body as one string — Partials/Page/
     * ThemeVars.html has no CSS syntax of its own to maintain, it only
     * prints this if it's non-empty.
     */
    private function buildCss(array $light, array $dark, array $pageMain): string
    {
        $css = '';
        $css .= $this->rule('[data-bs-theme="light"], :root:not([data-bs-theme="dark"])', $light);
        $css .= $this->rule('[data-bs-theme="dark"]', $dark);
        $css .= $this->rule('[data-bs-theme="light"] #main-content, :root:not([data-bs-theme="dark"]) #main-content', $pageMain);
        $css .= $this->rule('[data-bs-theme="dark"] #main-content', $pageMain);

        return $css;
    }

    /**
     * Overrides the --bs-btn-* vars of .btn-<variant> and .btn-outline-<variant>
     * for every variant that has a background and/or text color set. Hover and
     * active shades are derived from the background (15% / 20% darker, like
     * Bootstrap's own shade-color defaults).
     */
    private function buildButtonCss(string $scope, ?SiteSettings $settings, string $mode): string
    {
        if ($settings === null) {
            return '';
        }

        $css = '';
        foreach (self::BUTTON_VARIANTS as $variant) {
            $key = 'theme.' . $mode . '.btn' . ucfirst($variant);
            $bg = $this->stringSetting($settings, $key . 'Bg');
            $color = $this->stringSetting($settings, $key . 'Color');

            $filled = [];
            $outline = [];
            if ($bg !== '') {
                $hover = sprintf('color-mix(in srgb, %s 85%%, #000)', $bg);
                $active = sprintf('color-mix(in srgb, %s 80%%, #000)', $bg);
                $filled = [
                    '--bs-btn-bg' => $bg,
                    '--bs-btn-border-color' => $bg,
                    '--bs-btn-hover-bg' => $hover,
                    '--bs-btn-hover-border-color' => $hover,
                    '--bs-btn-active-bg' => $active,
                    '--bs-btn-active-border-color' => $active,
                    '--bs-btn-disabled-bg' => $bg,
                    '--bs-btn-disabled-border-color' => $bg,
                ];
                $outline = [
                    '--bs-btn-color' => $bg,
                    '--bs-btn-border-color' => $bg,
                    '--bs-btn-hover-bg' => $bg,
                    '--bs-btn-hover-border-color' => $bg,
                    '--bs-btn-active-bg' => $bg,
                    '--bs-btn-active-border-color' => $bg,
                    '--bs-btn-disabled-color' => $bg,
                    '--bs-btn-disabled-border-color' => $bg,
                ];
            }
            if ($color !== '') {
                $filled += [
                    '--bs-btn-color' => $color,
                    '--bs-btn-hover-color' => $color,
                    '--bs-btn-active-color' => $color,
                    '--bs-btn-disabled-color' => $color,
                ];
                $outline += [
                    '--bs-btn-hover-color' => $color,
                    '--bs-btn-active-color' => $color,
                ];
            }

            $css .= $this->rule(sprintf('%s .btn-%s', $scope, $variant), $filled);
            $css .= $this->rule(sprintf('%s .btn-outline-%s', $scope, $variant), $outline);
        }

        return $css;
    }

    /**
     * Only on :root — the tokens are declared there, and a custom property is
     * resolved where it is declared, so the scale must be set on the same element.
     */
    private function buildRadiusCss(?SiteSettings $settings): string
    {
        if ($settings === null) {
            return '';
        }
        $scale = self::RADIUS_SCALES[$this->stringSetting($settings, 'theme.radius')] ?? null;

        return $scale === null ? '' : $this->rule(':root', ['--conn-radius-scale' => $scale]);
    }

    /** navbar.shrinkOnScroll: logo height of the shrunk header (_navigation.scss). */
    private function buildNavbarShrinkCss(?SiteSettings $settings): string
    {
        if ($settings === null || !(bool)$settings->get('navbar.shrinkOnScroll', false)) {
            return '';
        }
        $height = (int)$settings->get('navbar.shrinkLogoHeight', 40);

        return $height > 0 ? $this->rule(':root', ['--conn-navbar-logo-height-shrunk' => $height . 'px']) : '';
    }

    /**
     * theme.*FontSizeScale → --conn-*-font-size-scale (used via theme/_tokens.scss).
     * Only plain numbers from 0.5 to 3 are output — anything else keeps the
     * default (1).
     */
    private function buildFontScaleCss(?SiteSettings $settings): string
    {
        if ($settings === null) {
            return '';
        }
        $declarations = [];
        $scales = [
            'theme.textFontSizeScale'        => '--conn-text-font-size-scale',
            'theme.headingFontSizeScale'     => '--conn-heading-font-size-scale',
            'theme.cardFontSizeScale'        => '--conn-card-font-size-scale',
            'theme.cardHeadingFontSizeScale' => '--conn-card-heading-font-size-scale',
        ];
        foreach ($scales as $path => $property) {
            $value = str_replace(',', '.', $this->stringSetting($settings, $path));
            if (is_numeric($value) && (float)$value >= 0.5 && (float)$value <= 3) {
                $declarations[$property] = (string)(float)$value;
            }
        }

        return $this->rule(':root', $declarations);
    }

    private function stringSetting(SiteSettings $settings, string $path): string
    {
        $value = $settings->get($path, '');

        return \is_string($value) ? trim($value) : '';
    }

    /** Empty declarations produce no output at all — not even an empty {} block. */
    private function rule(string $selector, array $declarations): string
    {
        if ($declarations === []) {
            return '';
        }

        $body = '';
        foreach ($declarations as $property => $value) {
            $body .= sprintf('%s:%s;', $property, $value);
        }

        return sprintf('%s{%s}', $selector, $body);
    }

    /** @param array<string, list<string>> $map */
    private function resolveSettings(?SiteSettings $settings, array $map): array
    {
        if ($settings === null) {
            return [];
        }

        $result = [];
        foreach ($map as $path => $properties) {
            $value = $settings->get($path, '');
            if ($value === '' || $value === null || is_array($value)) {
                continue;
            }
            foreach ($properties as $property) {
                $result[$property] = $value;
            }
        }

        return $result;
    }

    /** @param array<string, list<string>> $map */
    private function resolveRecord(array $record, array $map): array
    {
        $result = [];
        foreach ($map as $field => $properties) {
            $value = $record[$field] ?? '';
            if ($value === '' || $value === null) {
                continue;
            }
            foreach ($properties as $property) {
                $result[$property] = $value;
            }
        }

        return $result;
    }
}
