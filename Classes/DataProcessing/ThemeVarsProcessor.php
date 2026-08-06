<?php

declare(strict_types=1);

namespace Connetation\Base\DataProcessing;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Resolves site settings (plus the per-page tx_conn_base_*_color fields)
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
        'theme.light.footerHeaderText' => ['--conn-footer-header-text'],
        'theme.light.footerLink' => ['--conn-footer-link'],
        'theme.light.footerLinkHover' => ['--conn-footer-link-hover'],
        'theme.light.footerLinkHoverUnderline' => ['--conn-footer-link-hover-underline'],
        'theme.light.text' => ['--conn-text', '--bs-body-color'],
        'theme.light.heading' => ['--conn-heading', '--bs-heading-color'],
        'theme.light.emphasis' => ['--conn-emphasis', '--bs-emphasis-color'],
        'theme.light.textMuted' => ['--conn-text-muted', '--bs-secondary-color'],
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
        'theme.dark.footerHeaderText' => ['--conn-footer-header-text'],
        'theme.dark.footerLink' => ['--conn-footer-link'],
        'theme.dark.footerLinkHover' => ['--conn-footer-link-hover'],
        'theme.dark.footerLinkHoverUnderline' => ['--conn-footer-link-hover-underline'],
        'theme.dark.text' => ['--conn-text', '--bs-body-color'],
        'theme.dark.heading' => ['--conn-heading', '--bs-heading-color'],
        'theme.dark.emphasis' => ['--conn-emphasis', '--bs-emphasis-color'],
        'theme.dark.textMuted' => ['--conn-text-muted', '--bs-secondary-color'],
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
        'theme.shadowHover.color' => ['--conn-sh-color'],
    ];

    // Per-page tx_conn_base_*_color fields (pages table, not site settings)
    private const PAGE_OVERRIDE = [
        'tx_conn_base_bg_color' => ['--conn-bg', 'background-color'],
        'tx_conn_base_text_color' => ['--conn-text', '--bs-body-color', 'color'],
        'tx_conn_base_heading_color' => ['--conn-heading', '--bs-heading-color'],
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
            'css' => $this->buildCss($light, $dark, $pageMain),
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
