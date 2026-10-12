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

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Renders a responsive <img> with a srcset ladder of WebP renditions,
 * so every device gets an image matched to its CSS size × device pixel ratio.
 *
 * - Ladder widths are capped at the (cropped) source width, so the srcset
 *   never claims a resolution the file cannot actually deliver.
 * - srcset renditions are WebP; the src fallback keeps the original format.
 * - Honors the editor's crop from the backend image editor.
 * - SVG and GIF are served unprocessed (vector scales infinitely,
 *   processing would break GIF animation).
 * - Emits width/height attributes to prevent layout shift.
 * - When breakpoints is set, wraps the <img> in a <picture> with one
 *   <source> per breakpoint (smallest maxWidth first) — lets editors pick a
 *   different focus/crop per breakpoint instead of relying on
 *   object-fit: cover to center-crop the same (largest) image everywhere.
 *
 * Usage:
 *   <conn:responsiveImage image="{slide.imageFile}" alt="{slide.title}" title="{slide.imageFile.title}"
 *        widths="640,960,1280,1920,2560,3840" sizes="100vw"
 *        breakpoints="{0: {cropVariant: 'base', maxWidth: 575.98}, 1: {cropVariant: 'sm', maxWidth: 767.98}}"
 *        loading="eager" fetchpriority="high"
 *        class="conn-hero-slider__bg-img" />
 */
final class ResponsiveImageViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'img';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('image', 'object', 'FAL File or FileReference', true);
        $this->registerArgument('widths', 'string', 'Comma-separated srcset width ladder in px', false, '480,768,1024,1440,1920');
        $this->registerArgument('sizes', 'string', 'sizes attribute (rendered CSS width per breakpoint)', false, '100vw');
        $this->registerArgument('alt', 'string', 'Alternative text', false, '');
        $this->registerArgument('title', 'string', 'Title attribute (tooltip); omitted entirely when empty', false, '');
        $this->registerArgument('aspectRatio', 'string', 'Crop every rendition to this ratio, e.g. "16:9" (empty = keep source ratio)', false, '');
        $this->registerArgument('loading', 'string', 'lazy | eager', false, 'lazy');
        $this->registerArgument('fetchpriority', 'string', 'high | low | auto (empty = attribute omitted)', false, '');
        $this->registerArgument('cropVariant', 'string', 'Crop variant name', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Target format for srcset renditions', false, 'webp');
        $this->registerArgument('breakpoints', 'array', 'List of {cropVariant, maxWidth, widths?, aspectRatio?, sizes?} rendered as <picture> <source> elements, one per breakpoint (empty = disabled, renders a plain <img>)', false, []);
    }

    public function render(): string
    {
        $imageService = GeneralUtility::makeInstance(ImageService::class);

        try {
            $image = $imageService->getImage('', $this->arguments['image'], false);
        } catch (\UnexpectedValueException | \InvalidArgumentException) {
            return '';
        }

        $this->tag->addAttribute('alt', (string)$this->arguments['alt']);
        if ($this->arguments['title'] !== '') {
            $this->tag->addAttribute('title', (string)$this->arguments['title']);
        }
        $this->tag->addAttribute('loading', (string)$this->arguments['loading']);
        $this->tag->addAttribute('decoding', 'async');
        if ($this->arguments['fetchpriority'] !== '') {
            $this->tag->addAttribute('fetchpriority', (string)$this->arguments['fetchpriority']);
        }

        $sourceExtension = strtolower((string)$image->getExtension());
        if (in_array($sourceExtension, ['svg', 'gif'], true)) {
            $this->tag->addAttribute('src', $imageService->getImageUri($image));
            if ((int)$image->getProperty('width') > 0) {
                $this->tag->addAttribute('width', (string)(int)$image->getProperty('width'));
                $this->tag->addAttribute('height', (string)(int)$image->getProperty('height'));
            }
            return $this->tag->render();
        }

        $cropString = $image->hasProperty('crop') && $image->getProperty('crop')
            ? (string)$image->getProperty('crop')
            : '';
        $cropVariants = CropVariantCollection::create($cropString);
        $crop = $this->resolveCropArea($cropVariants, (string)$this->arguments['cropVariant'], $image);

        $sourceWidth = $crop !== null ? (int)floor($crop->asArray()['width']) : (int)$image->getProperty('width');
        $requested = $this->parseWidths((string)$this->arguments['widths'], 1920);
        $widths = $this->selectWidths($requested, $sourceWidth);

        $ratio = $this->parseRatio((string)$this->arguments['aspectRatio']);
        $targetExtension = (string)$this->arguments['fileExtension'];

        $srcsetEntries = $this->buildSrcSet($imageService, $image, $widths, $ratio, $crop, $targetExtension);

        // src fallback in the original format for browsers without srcset/WebP support
        $fallback = $imageService->applyProcessingInstructions(
            $image,
            $this->processingInstructions(min(1920, max($widths)), $ratio, $crop, null)
        );

        $this->tag->addAttribute('src', $imageService->getImageUri($fallback));
        if ($srcsetEntries !== []) {
            $this->tag->addAttribute('srcset', implode(', ', $srcsetEntries));
            $this->tag->addAttribute('sizes', (string)$this->arguments['sizes']);
        }
        if ((int)$fallback->getProperty('width') > 0) {
            $this->tag->addAttribute('width', (string)(int)$fallback->getProperty('width'));
            $this->tag->addAttribute('height', (string)(int)$fallback->getProperty('height'));
        }

        $imgTag = $this->tag->render();

        $breakpoints = $this->arguments['breakpoints'];
        if (!is_array($breakpoints) || $breakpoints === []) {
            return $imgTag;
        }

        // Smallest maxWidth first, so the browser hits the most specific
        // <source> before falling through to wider (or the <img>) ones.
        usort($breakpoints, static fn(array $a, array $b): int
            => ((float)($a['maxWidth'] ?? 0)) <=> ((float)($b['maxWidth'] ?? 0)));

        $sources = '';
        foreach ($breakpoints as $breakpoint) {
            $variantName = (string)($breakpoint['cropVariant'] ?? '');
            if ($variantName === '' || $variantName === (string)$this->arguments['cropVariant']) {
                continue;
            }

            $maxWidth = $breakpoint['maxWidth'] ?? null;
            if ($maxWidth === null) {
                continue;
            }
            $maxWidthPx = (int)round((float)$maxWidth);

            $breakpointCrop = $this->resolveCropArea($cropVariants, $variantName, $image);
            $breakpointSourceWidth = $breakpointCrop !== null
                ? (int)floor($breakpointCrop->asArray()['width'])
                : (int)$image->getProperty('width');

            $requestedStr = (string)($breakpoint['widths'] ?? '');
            if ($requestedStr === '') {
                $requestedStr = $maxWidthPx . ',' . ($maxWidthPx * 2);
            }
            $breakpointWidths = $this->selectWidths($this->parseWidths($requestedStr, $maxWidthPx), $breakpointSourceWidth);

            $breakpointRatioArg = (string)($breakpoint['aspectRatio'] ?? '');
            $breakpointRatio = $breakpointRatioArg !== '' ? $this->parseRatio($breakpointRatioArg) : $ratio;

            $entries = $this->buildSrcSet($imageService, $image, $breakpointWidths, $breakpointRatio, $breakpointCrop, $targetExtension);
            if ($entries === []) {
                continue;
            }

            $sourceTag = new TagBuilder('source');
            $sourceTag->addAttribute('media', '(max-width: ' . $maxWidth . 'px)');
            $sourceTag->addAttribute('srcset', implode(', ', $entries));
            $sourceTag->addAttribute('sizes', (string)($breakpoint['sizes'] ?? $this->arguments['sizes']));
            $sourceTag->addAttribute('type', 'image/' . $targetExtension);
            $sources .= $sourceTag->render();
        }

        if ($sources === '') {
            return $imgTag;
        }

        return '<picture>' . $sources . $imgTag . '</picture>';
    }

    private function resolveCropArea(CropVariantCollection $cropVariants, string $variantName, FileInterface $image): ?Area
    {
        $cropArea = $cropVariants->getCropArea($variantName);
        return $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image);
    }

    /**
     * @return int[]
     */
    private function parseWidths(string $value, int $fallback): array
    {
        $requested = array_map('intval', GeneralUtility::trimExplode(',', $value, true));
        $requested = array_values(array_unique(array_filter($requested, static fn(int $w): bool => $w > 0)));
        sort($requested);
        return $requested === [] ? [$fallback] : $requested;
    }

    /**
     * @param int[] $requested
     * @return int[]
     */
    private function selectWidths(array $requested, int $sourceWidth): array
    {
        if ($sourceWidth <= 0) {
            return $requested;
        }
        $widths = array_values(array_filter($requested, static fn(int $w): bool => $w <= $sourceWidth));
        if ($widths === []) {
            // Source is smaller than every ladder step — offer what it has
            return [$sourceWidth];
        }
        if ($sourceWidth > max($widths) && max($widths) < max($requested)) {
            // Source sits between ladder steps — expose its full native sharpness
            $widths[] = $sourceWidth;
        }
        return $widths;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function parseRatio(string $value): ?array
    {
        if (preg_match('/^(\d+)\s*[:\/]\s*(\d+)$/', $value, $matches) && (int)$matches[2] > 0) {
            return [(int)$matches[1], (int)$matches[2]];
        }
        return null;
    }

    /**
     * @param int[] $widths
     * @param array{0: int, 1: int}|null $ratio
     * @return array<int, string>
     */
    private function buildSrcSet(ImageService $imageService, FileInterface $image, array $widths, ?array $ratio, ?Area $crop, string $targetExtension): array
    {
        $entries = [];
        foreach ($widths as $width) {
            $processed = $imageService->applyProcessingInstructions(
                $image,
                $this->processingInstructions($width, $ratio, $crop, $targetExtension)
            );
            $renditionWidth = (int)$processed->getProperty('width');
            if ($renditionWidth > 0) {
                $entries[$renditionWidth] = $imageService->getImageUri($processed) . ' ' . $renditionWidth . 'w';
            }
        }
        ksort($entries);
        return $entries;
    }

    /**
     * @param array{0: int, 1: int}|null $ratio
     */
    private function processingInstructions(int $width, ?array $ratio, ?Area $crop, ?string $fileExtension): array
    {
        $instructions = ['crop' => $crop];
        if ($ratio !== null) {
            $instructions['width'] = $width . 'c';
            $instructions['height'] = (int)round($width * $ratio[1] / $ratio[0]) . 'c';
        } else {
            $instructions['width'] = (string)$width;
        }
        if ($fileExtension !== null && $fileExtension !== '') {
            $instructions['fileExtension'] = $fileExtension;
        }
        return $instructions;
    }
}
