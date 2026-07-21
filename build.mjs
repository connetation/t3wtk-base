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
    await Promise.all([ctx.watch(), ckeditorCtx.watch()]);
    console.log('[base] watching…');
} else {
    await esbuild.build(mainConfig);
    await esbuild.build(ckeditorEmojiConfig);
    console.log('[base] built');
}
