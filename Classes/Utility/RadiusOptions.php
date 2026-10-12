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
 * The one list of per-element "Border radius" choices for every conn/* package.
 *
 *  - Backend: every radius select uses {@see addItems()} as its itemsProcFunc
 *    (with an empty <items>), so labels and order live only here.
 *  - Frontend: data processors validate with {@see resolve()} and templates
 *    output the result as data-radius="…"; the matching CSS sizes come from
 *    $conn-radius-options / the conn-radius-option() mixin in
 *    Resources/Private/Scss/theme/_tokens.scss. Keep VALUES and that map in step.
 *
 * "theme" follows the site's Corner radius setting (theme.radius) and is the
 * default; every other option is a fixed size that ignores it — choosing one
 * overrides the site setting for that element only.
 */
final class RadiusOptions
{
    public const THEME_DEFAULT = 'theme';

    public const VALUES = ['theme', 'none', 'small', 'medium', 'large', 'pill'];

    private const LLL = 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:radius.option.';

    /** itemsProcFunc: Connetation\T3wtkBase\Utility\RadiusOptions->addItems */
    public function addItems(array &$parameters): void
    {
        foreach (self::VALUES as $value) {
            $parameters['items'][] = ['label' => self::LLL . $value, 'value' => $value];
        }
    }

    /** A valid option, else $default (for unset/unknown/legacy values). */
    public static function resolve(mixed $value, string $default = self::THEME_DEFAULT): string
    {
        return \in_array($value, self::VALUES, true) ? $value : $default;
    }
}
