const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/main.js', 'public/build/js')
   .js('resources/js/admin.js', 'public/build/js')
   .js('resources/js/forum.js', 'public/build/js')
   .js('resources/js/editor.js', 'public/build/js')
   .js('resources/js/search.js', 'public/build/js')
   .js('resources/js/notifications.js', 'public/build/js')
   .sass('resources/css/app.scss', 'public/build/css')
   .sass('resources/css/admin.scss', 'public/build/css')
   .sass('resources/css/responsive.scss', 'public/build/css')
   .sass('resources/css/utilities.scss', 'public/build/css')
   .options({
       processCssUrls: false
   })
   .version()
   .sourceMaps();

if (mix.inProduction()) {
    mix.version();
}