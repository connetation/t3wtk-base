// Resources/Private/JavaScript/devices.ts
var TABLET_MIN = 768;
var DESKTOP_MIN = 1200;
var MQ_MOBILE = `(max-width: ${TABLET_MIN - 0.02}px)`;
var MQ_BELOW_DESKTOP = `(max-width: ${DESKTOP_MIN - 0.02}px)`;
export {
  DESKTOP_MIN,
  MQ_BELOW_DESKTOP,
  MQ_MOBILE,
  TABLET_MIN
};
