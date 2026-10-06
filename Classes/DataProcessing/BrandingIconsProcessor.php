<?php

declare(strict_types=1);

namespace Connetation\T3wtkBase\DataProcessing;

use Connetation\T3wtkBase\Middleware\BrandingIconsMiddleware;
use Connetation\T3wtkBase\Utility\ResourcePathUtility;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Builds the <link> tags for Templates/Page/Head/Favicon.html from the branding.* site settings,
 * in the order browsers need them: ICO with sizes="32x32" first (so SVG-capable browsers
 * prefer the SVG), then the main favicon, apple-touch-icon and the web manifest.
 */
final class BrandingIconsProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $site = $cObj->getRequest()->getAttribute('site');
        $links = [];

        if ($site instanceof Site) {
            $settings = $site->getSettings();

            $ico = ResourcePathUtility::webPath((string)$settings->get('branding.faviconIco', ''));
            if ($ico !== '') {
                $links[] = ['rel' => 'icon', 'href' => $ico, 'sizes' => '32x32'];
            }

            $favicon = ResourcePathUtility::webPath((string)$settings->get('branding.favicon', ''));
            if ($favicon !== '') {
                $type = ResourcePathUtility::mimeType($favicon);
                $links[] = ['rel' => 'icon', 'href' => $favicon, 'type' => $type, 'sizes' => $type === 'image/svg+xml' ? 'any' : ''];
            }

            $appleTouchIcon = ResourcePathUtility::webPath((string)$settings->get('branding.appleTouchIcon', ''));
            if ($appleTouchIcon !== '') {
                $links[] = ['rel' => 'apple-touch-icon', 'href' => $appleTouchIcon, 'sizes' => '180x180'];
            }

            if (BrandingIconsMiddleware::manifestIcons($settings) !== []) {
                $links[] = ['rel' => 'manifest', 'href' => BrandingIconsMiddleware::basePath($site) . BrandingIconsMiddleware::MANIFEST_FILENAME];
            }
        }

        $processedData[$processorConfiguration['as'] ?? 'brandingIcons'] = $links;
        return $processedData;
    }
}
