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
 * The one list of "Card design" choices for every card element in every
 * conn/* package:
 *
 *  - standard: image above the text (the card's normal look)
 *  - image:    the card's image fills the card, text on top of it (with an
 *              overlay); cards without an image use the background color
 *  - color:    the background color fills the card, no image
 *
 * Colors (background, overlay, text) are set once per element.
 *
 *  - Backend: card-design selects use {@see addItems()} (or
 *    {@see addColorItems()} for cards whose image is only an icon).
 *  - Frontend: data processors use {@see resolveSettings()}; the card wrapper
 *    gets `cardDesignClass` + `cardDesignStyle`, each card the
 *    conn-card-design__card/__media/__placeholder classes
 *    (Scss/components/_card-design.scss).
 */
final class CardDesignOptions
{
    public const DEFAULT = 'standard';

    public const VALUES = ['standard', 'image', 'color'];

    public const DEFAULTS = [
        'cardDesign'      => self::DEFAULT,
        'cardDesignClass' => 'conn-card-design conn-card-design--standard',
        'cardDesignStyle' => '',
    ];

    private const LLL = 'LLL:EXT:conn_t3wtk_base/Resources/Private/Language/locallang_be.xlf:card_design.option.';

    /** itemsProcFunc: Connetation\T3wtkBase\Utility\CardDesignOptions->addItems */
    public function addItems(array &$parameters): void
    {
        foreach (self::VALUES as $value) {
            $parameters['items'][] = ['label' => self::LLL . $value, 'value' => $value];
        }
    }

    /** itemsProcFunc for cards whose image is an icon: no "image in background". */
    public function addColorItems(array &$parameters): void
    {
        foreach (array_diff(self::VALUES, ['image']) as $value) {
            $parameters['items'][] = ['label' => self::LLL . $value, 'value' => $value];
        }
    }

    /** A valid option, else $default (for unset/unknown values). */
    public static function resolve(mixed $value, string $default = self::DEFAULT): string
    {
        return \in_array($value, self::VALUES, true) ? $value : $default;
    }

    /**
     * cardDesign, cardDesignClass and cardDesignStyle (CSS custom properties
     * for the colors) from a raw settings array.
     *
     * @return array{cardDesign: string, cardDesignClass: string, cardDesignStyle: string}
     */
    public static function resolveSettings(array $raw): array
    {
        $design = self::resolve($raw['cardDesign'] ?? null);
        $style = '';
        if ($design !== 'standard') {
            foreach ([
                'cardBackgroundColor' => '--conn-card-design-bg',
                'cardOverlayColor'    => '--conn-card-design-overlay',
                'cardTextColor'       => '--conn-card-design-text',
            ] as $key => $property) {
                $color = self::sanitizeColor((string)($raw[$key] ?? ''));
                if ($color !== '') {
                    $style .= $property . ': ' . $color . '; ';
                }
            }
        }

        return [
            'cardDesign'      => $design,
            'cardDesignClass' => 'conn-card-design conn-card-design--' . $design,
            'cardDesignStyle' => trim($style),
        ];
    }

    /** A color picker value (#RGB … #RRGGBBAA), else ''. */
    private static function sanitizeColor(string $value): string
    {
        $value = trim($value);

        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1 ? $value : '';
    }
}
