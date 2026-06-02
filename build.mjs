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

const componentsConfig = {
    entryPoints: {
        'navbar-megamenu-twopanel': 'Resources/Private/TypeScript/Navbar/navbar-megamenu-twopanel.ts',
    },
    bundle:   true,
    format:   'esm',
    outdir:   'Resources/Public/Js/components',
    minify:   !dev,
    sourcemap: dev ? 'inline' : false,
};

if (watch) {
    const [mainCtx, compCtx] = await Promise.all([
        esbuild.context(mainConfig),
        esbuild.context(componentsConfig),
    ]);
    await Promise.all([mainCtx.watch(), compCtx.watch()]);
    console.log('[sitepackage] watching…');
} else {
    await Promise.all([
        esbuild.build(mainConfig),
        esbuild.build(componentsConfig),
    ]);
    console.log('[sitepackage] built');
}
