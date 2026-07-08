<?php

declare(strict_types=1);

namespace Connetation\Base\ViewHelpers;

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
 * - When mobileCropVariant is set, wraps the <img> in a <picture> with a
 *   <source> for that variant below mobileMediaQuery — lets editors pick a
 *   different focus/crop for narrow viewports instead of relying on
 *   object-fit: cover to center-crop the desktop image.
 *
 * Usage:
 *   <conn:responsiveImage image="{slide.imageFile}" alt="{slide.title}"
 *        widths="640,960,1280,1920,2560,3840" sizes="100vw"
 *        mobileCropVariant="mobile"
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
        $this->registerArgument('aspectRatio', 'string', 'Crop every rendition to this ratio, e.g. "16:9" (empty = keep source ratio)', false, '');
        $this->registerArgument('loading', 'string', 'lazy | eager', false, 'lazy');
        $this->registerArgument('fetchpriority', 'string', 'high | low | auto (empty = attribute omitted)', false, '');
        $this->registerArgument('cropVariant', 'string', 'Crop variant name', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Target format for srcset renditions', false, 'webp');
        $this->registerArgument('mobileCropVariant', 'string', 'Crop variant rendered via a <picture> <source> below mobileMediaQuery (empty = disabled, renders a plain <img>)', false, '');
        $this->registerArgument('mobileAspectRatio', 'string', 'Aspect ratio for the mobile source, e.g. "3:4" (empty = reuse aspectRatio)', false, '');
        $this->registerArgument('mobileWidths', 'string', 'Comma-separated srcset width ladder in px for the mobile source', false, '480,640,828,1080');
        $this->registerArgument('mobileSizes', 'string', 'sizes attribute for the mobile source', false, '100vw');
        $this->registerArgument('mobileMediaQuery', 'string', 'media attribute for the mobile <source>', false, '(max-width: 767.98px)');
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

        $mobileCropVariant = (string)$this->arguments['mobileCropVariant'];
        if ($mobileCropVariant === '' || $mobileCropVariant === (string)$this->arguments['cropVariant']) {
            return $imgTag;
        }

        $mobileCrop = $this->resolveCropArea($cropVariants, $mobileCropVariant, $image);
        $mobileSourceWidth = $mobileCrop !== null ? (int)floor($mobileCrop->asArray()['width']) : (int)$image->getProperty('width');
        $mobileRequested = $this->parseWidths((string)$this->arguments['mobileWidths'], 1080);
        $mobileWidths = $this->selectWidths($mobileRequested, $mobileSourceWidth);

        $mobileRatioArg = (string)$this->arguments['mobileAspectRatio'];
        $mobileRatio = $mobileRatioArg !== '' ? $this->parseRatio($mobileRatioArg) : $ratio;

        $mobileSrcsetEntries = $this->buildSrcSet($imageService, $image, $mobileWidths, $mobileRatio, $mobileCrop, $targetExtension);
        if ($mobileSrcsetEntries === []) {
            return $imgTag;
        }

        $sourceTag = new TagBuilder('source');
        $sourceTag->addAttribute('media', (string)$this->arguments['mobileMediaQuery']);
        $sourceTag->addAttribute('srcset', implode(', ', $mobileSrcsetEntries));
        $sourceTag->addAttribute('sizes', (string)$this->arguments['mobileSizes']);
        $sourceTag->addAttribute('type', 'image/' . $targetExtension);

        return '<picture>' . $sourceTag->render() . $imgTag . '</picture>';
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
