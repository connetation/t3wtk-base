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

namespace Connetation\T3wtkBase\Utility;

/**
 * The one list of per-element "Font weight" choices for card titles,
 * subtitles and text in every conn/* package.
 *
 *  - Backend: every font-weight select uses {@see addItems()} as its
 *    itemsProcFunc (with an empty <items>), so labels and order live only here.
 *  - Frontend: data processors validate with {@see resolve()} /
 *    {@see resolveCard()} and templates output the value as a class.
 *
 * VALUES are Bootstrap's own font-weight utilities (all `!important`), so the
 * chosen weight always wins over the component's own CSS.
 */
final class FontWeightOptions
{
    public const VALUES = ['fw-lighter', 'fw-light', 'fw-normal', 'fw-medium', 'fw-semibold', 'fw-bold', 'fw-bolder'];

    /** Card defaults: everything normal (news/event cards pass a bold title). */
    public const CARD_DEFAULTS = [
        'titleWeight'    => 'fw-normal',
        'subtitleWeight' => 'fw-normal',
        'textWeight'     => 'fw-normal',
    ];

    private const LLL = 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:font_weight.option.';

    /** itemsProcFunc: Connetation\T3wtkBase\Utility\FontWeightOptions->addItems */
    public function addItems(array &$parameters): void
    {
        foreach (self::VALUES as $value) {
            $parameters['items'][] = ['label' => self::LLL . $value, 'value' => $value];
        }
    }

    /** A valid option, else $default (for unset/unknown values). */
    public static function resolve(mixed $value, string $default = 'fw-normal'): string
    {
        return \in_array($value, self::VALUES, true) ? $value : $default;
    }

    /**
     * titleWeight / subtitleWeight / textWeight from a raw settings array,
     * each falling back to $defaults (merged over CARD_DEFAULTS).
     *
     * @return array{titleWeight: string, subtitleWeight: string, textWeight: string}
     */
    public static function resolveCard(array $raw, array $defaults = []): array
    {
        $resolved = [];
        foreach (array_replace(self::CARD_DEFAULTS, $defaults) as $key => $default) {
            $resolved[$key] = self::resolve($raw[$key] ?? null, $default);
        }

        return $resolved;
    }
}
