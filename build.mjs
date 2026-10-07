import * as esbuild from 'esbuild';

const watch = process.argv.includes('--watch');
const dev   = watch || process.argv.includes('--dev');

const mainConfig = {
    entryPoints: ['Resources/Private/JavaScript/main.js'],
    bundle:   true,
    outfile:  'Resources/Public/Js/main.js',
    minify:   !dev,
    sourcemap: dev ? 'inline' : false,
};

// Device breakpoints (the TS copy of Classes/Utility/Devices.php) as a backend
// ES module, imported by the site setting type connResponsive
// (Resources/Public/JavaScript/settings/responsive.js).
const devicesConfig = {
    entryPoints: ['Resources/Private/JavaScript/devices.ts'],
    bundle:   true,
    format:   'esm',
    outfile:  'Resources/Public/JavaScript/devices.js',
    minify:   false,
    sourcemap: false,
};

const ckeditorEmojiConfig = {
    entryPoints: ['Resources/Private/JavaScript/CKEditor/ckeditor5-emoji.js'],
    bundle:   true,
    format:   'esm',
    outfile:  'Resources/Public/JavaScript/CKEditor/ckeditor5-emoji.js',
    minify:   !dev,
    sourcemap: dev ? 'inline' : false,
    external: [
        '@ckeditor/ckeditor5-core/dist/index.js',
        '@ckeditor/ckeditor5-utils/dist/index.js',
        '@ckeditor/ckeditor5-typing/dist/index.js',
        '@ckeditor/ckeditor5-ui/dist/index.js',
        '@ckeditor/ckeditor5-icons/dist/index.js',
    ],
};

if (watch) {
    const ctx = await esbuild.context(mainConfig);
    const ckeditorCtx = await esbuild.context(ckeditorEmojiConfig);
    const devicesCtx = await esbuild.context(devicesConfig);
    await Promise.all([ctx.watch(), ckeditorCtx.watch(), devicesCtx.watch()]);
    console.log('[base] watching…');
} else {
    await esbuild.build(mainConfig);
    await esbuild.build(ckeditorEmojiConfig);
    await esbuild.build(devicesConfig);
    console.log('[base] built');
}
