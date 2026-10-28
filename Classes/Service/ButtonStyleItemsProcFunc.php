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

namespace Connetation\T3wtkBase\Service;

use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Button style choices taken from the page's RTE preset — the same `btn …`
 * class strings the editor offers in the RTE style dropdown and link browser,
 * so site packages that add their own buttons there get them everywhere a
 * "Link style" select is offered (cards, pricing, news/event lists, …).
 *
 *  - addItems(): only the RTE buttons (a button is always required)
 *  - addItemsWithDefault(): "Element default" ('') first, then the RTE
 *    buttons — the element keeps its own button until one is chosen
 *  - resolveSettings(): validated `linkStyle` for data processors
 */
class ButtonStyleItemsProcFunc
{
    public const DEFAULT = 'btn btn-primary';

    private const LLL = 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:';

    public function __construct(
        private readonly Richtext $richtext,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /** itemsProcFunc: Connetation\T3wtkBase\Service\ButtonStyleItemsProcFunc->addItems */
    public function addItems(array &$parameters): void
    {
        $pid = (int)($parameters['effectivePid'] ?? $parameters['flexParentDatabaseRow']['pid'] ?? 0);
        $bodytextConfig = array_replace(
            $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config'] ?? [],
            $GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides']['bodytext']['config'] ?? []
        );
        $rte = $this->richtext->getConfiguration('tt_content', 'bodytext', max($pid, 0), 'text', $bodytextConfig);

        $classes = $rte['buttons']['link']['properties']['class']['allowedClasses'] ?? [];
        $names = $rte['classes'] ?? [];
        $languageService = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);

        $seen = [];
        foreach ($classes as $class) {
            $class = trim((string)$class);
            if (!self::isButtonClass($class) || isset($seen[$class])) {
                continue;
            }
            $seen[$class] = true;
            $label = (string)($names[$class]['name'] ?? $class);
            $parameters['items'][] = ['label' => $languageService->sL($label) ?: $label, 'value' => $class];
        }

        if ($seen === []) {
            $parameters['items'][] = ['label' => 'Button primary', 'value' => self::DEFAULT];
        }
    }

    /** itemsProcFunc: Connetation\T3wtkBase\Service\ButtonStyleItemsProcFunc->addItemsWithDefault */
    public function addItemsWithDefault(array &$parameters): void
    {
        $parameters['items'][] = ['label' => self::LLL . 'link_style.default', 'value' => ''];
        $this->addItems($parameters);
    }

    /** A class string that is safe to output and contains the `btn` class. */
    public static function isButtonClass(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9_\- ]+$/', $value) === 1
            && in_array('btn', preg_split('/\s+/', trim($value)) ?: [], true);
    }

    /**
     * linkStyle from a raw settings array: a valid button class string, or ''
     * (= the element's own default button).
     *
     * @return array{linkStyle: string}
     */
    public static function resolveSettings(array $raw, string $key = 'linkStyle'): array
    {
        $value = trim((string)($raw[$key] ?? ''));

        return ['linkStyle' => self::isButtonClass($value) ? $value : ''];
    }
}
