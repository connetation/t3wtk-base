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
