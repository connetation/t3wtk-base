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

namespace Connetation\T3wtkBase\Utility;

/**
 * The one list of per-element "Card alignment" choices for every card grid
 * in every conn/* package: where the cards of a row that isn't full go
 * (e.g. 5 cards at 3 per row → 3 on top, 2 below at start/center/end).
 *
 *  - Backend: every card-alignment select uses {@see addItems()} as its
 *    itemsProcFunc (with an empty <items>), so labels and order live only here.
 *  - Frontend: data processors validate with {@see resolveSettings()};
 *    Bootstrap row grids output it as `justify-content-<value>`, CSS grids as
 *    `data-card-align="<value>"` (Scss/theme/_devices.scss card-align()): a
 *    CSS grid can't move an incomplete last row, so the mixin turns it into a
 *    wrapping flex row with the same column widths on devices whose column
 *    count is known; other devices keep the grid.
 */
final class CardAlignOptions
{
    public const DEFAULT = 'start';

    public const VALUES = ['start', 'center', 'end'];

    public const DEFAULTS = ['cardAlign' => self::DEFAULT];

    private const LLL = 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_align.option.';

    /** itemsProcFunc: Connetation\T3wtkBase\Utility\CardAlignOptions->addItems */
    public function addItems(array &$parameters): void
    {
        foreach (self::VALUES as $value) {
            $parameters['items'][] = ['label' => self::LLL . $value, 'value' => $value];
        }
    }

    /** A valid option, else $default (for unset/unknown values). */
    public static function resolve(mixed $value, string $default = self::DEFAULT): string
    {
        return \in_array($value, self::VALUES, true) ? $value : $default;
    }

    /**
     * cardAlign from a raw settings array.
     *
     * @return array{cardAlign: string}
     */
    public static function resolveSettings(array $raw): array
    {
        return ['cardAlign' => self::resolve($raw['cardAlign'] ?? null)];
    }
}
