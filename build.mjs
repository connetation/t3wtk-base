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

if (watch) {
    const ctx = await esbuild.context(mainConfig);
    await ctx.watch();
    console.log('[base] watching…');
} else {
    await esbuild.build(mainConfig);
    console.log('[base] built');
}
