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

namespace Connetation\T3wtkBase\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

/**
 * Rebuilds the 'frames' palette showitem so related fields stay grouped on
 * fixed rows (frame/width, background type, background fields, spacing),
 * regardless of which order extensions inserted their fields in — e.g.
 * conn_t3wtk_elements' optional tx_conn_t3wtk_elements_text_animation field landing
 * between frame_class and tx_conn_t3wtk_base_content_width purely because its
 * addFieldsToPalette('after:frame_class', ...) call happened to run after
 * conn_t3wtk_base's.
 */
#[AsEventListener('conn-t3wtk-base/reorder-frames-palette')]
final class ReorderFramesPalette
{
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        $columns = $tca['tt_content']['columns'] ?? [];

        if (!isset($columns['tx_conn_t3wtk_base_content_width'])) {
            return;
        }

        $frameGroup = ['layout', 'frame_class', 'tx_conn_t3wtk_base_content_width'];

        $rows = [implode(',', $frameGroup)];

        if (isset($columns['tx_conn_t3wtk_elements_text_animation'])) {
            $rows[] = 'tx_conn_t3wtk_elements_text_animation';
        }

        $rows[] = 'tx_conn_t3wtk_base_bg_type';
        $rows[] = 'tx_conn_t3wtk_base_bg_color,tx_conn_t3wtk_base_bg_text_color,tx_conn_t3wtk_base_bg_image,tx_conn_t3wtk_base_bg_mode';
        $rows[] = 'tx_conn_t3wtk_base_bg_padding_before,tx_conn_t3wtk_base_bg_padding_after';
        $rows[] = 'space_before_class,space_after_class';

        $tca['tt_content']['palettes']['frames']['showitem'] = implode(',--linebreak--,', $rows);

        $event->setTca($tca);
    }
}
