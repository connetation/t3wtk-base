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

/**
 * Device breakpoints for every per-device setting (renderType
 * connResponsiveRange, stored as "desktop,tablet,mobile").
 * Reference: Classes/Utility/Devices.php — keep these values in step with it
 * (SCSS counterpart: Resources/Private/Scss/theme/_devices.scss). Shared by
 * all conn/* packages.
 *   Mobile < 768px · Tablet 768–1199px · Desktop ≥ 1200px
 */
export const TABLET_MIN  = 768;
export const DESKTOP_MIN = 1200;

/** Media queries for matchMedia(). */
export const MQ_MOBILE        = `(max-width: ${TABLET_MIN - 0.02}px)`;
export const MQ_BELOW_DESKTOP = `(max-width: ${DESKTOP_MIN - 0.02}px)`;
