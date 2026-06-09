<?php

declare(strict_types=1);

namespace Connetation\Base\DataProcessing;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class PageDataProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $processedData['pageData'] = [
            'subtitle'      => $processedData['data']['tx_conn_base_subtitle'] ?? '',
            'hideBreadcrumb'=> (bool)($processedData['data']['tx_conn_base_hide_breadcrumb'] ?? false),
        ];

        return $processedData;
    }
}
