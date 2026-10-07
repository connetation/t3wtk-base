<?php

declare(strict_types=1);

namespace Connetation\T3wtkBase\Settings;

use Connetation\T3wtkBase\Utility\Devices;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsTypeInterface;

/**
 * Site setting type "connResponsive": one value per device (Desktop / Tablet /
 * Mobile, breakpoints from Utility\Devices), edited with device tabs in the
 * Site Settings module and stored as "desktop,tablet,mobile" (e.g. "4,6,12";
 * an empty tablet/mobile value = same as the next larger device).
 *
 * Use it for any setting area (header, footer, …) in settings.definitions.yaml:
 *
 *   footer.col1.width:
 *     type: connResponsive
 *     default: '4,6,12'
 *     options:
 *       min: 1          # number mode: slider + number field
 *       max: 12
 *       step: 1
 *       unit: ''        # optional, shown next to the value (e.g. px, vh)
 *
 *   footer.col1.align:
 *     type: connResponsive
 *     default: 'start'
 *     options:
 *       choices:        # choice mode: one select per device
 *         start: 'Left'
 *         center: 'Center'
 *         end: 'Right'
 *
 * In templates, turn the value into Bootstrap classes with
 * <conn:responsiveClasses value="{site.settings.footer.col1.width}" pattern="col[bp]-[value]" />
 * or read it in PHP via Utility\ResponsiveValue.
 */
#[AsTaggedItem(index: 'connResponsive')]
readonly class ResponsiveSettingType implements SettingsTypeInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function validate(mixed $value, SettingDefinition $definition): bool
    {
        return $this->normalize($value, $definition) !== null;
    }

    public function transformValue(mixed $value, SettingDefinition $definition): string
    {
        $normalized = $this->normalize($value, $definition);
        if ($normalized === null) {
            $this->logger->warning('Setting validation failed, reverting to default: {key}', ['key' => $definition->key]);
            return (string)$definition->default;
        }

        return $normalized;
    }

    public function getJavaScriptModule(): string
    {
        return '@typo3/conn-t3wtk-base/settings/responsive.js';
    }

    /** "desktop,tablet,mobile" with trailing empty values dropped, or null when invalid. */
    private function normalize(mixed $value, SettingDefinition $definition): ?string
    {
        if (!\is_string($value) && !\is_int($value) && !\is_float($value)) {
            return null;
        }
        $parts = array_map('trim', explode(',', (string)$value));
        if (\count($parts) > \count(Devices::ALL) || $parts[0] === '') {
            return null;
        }
        foreach ($parts as $part) {
            if ($part !== '' && !$this->isValidPart($part, $definition->options)) {
                return null;
            }
        }
        while (\count($parts) > 1 && end($parts) === '') {
            array_pop($parts);
        }

        return implode(',', $parts);
    }

    private function isValidPart(string $part, array $options): bool
    {
        // settings.definitions.yaml is parsed with maps as objects, and core
        // only casts the top level of "options" to an array.
        $choices = $options['choices'] ?? null;
        if (\is_object($choices)) {
            $choices = (array)$choices;
        }
        if (\is_array($choices)) {
            return array_key_exists($part, $choices);
        }
        if (!is_numeric($part)) {
            return false;
        }
        $number = (float)$part;

        return (!isset($options['min']) || $number >= (float)$options['min'])
            && (!isset($options['max']) || $number <= (float)$options['max']);
    }
}
