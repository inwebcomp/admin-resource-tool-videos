let mix = require('laravel-mix')

mix.setPublicPath('dist')
   .js('resources/js/resource-tool.js', 'js')
   .sass('resources/scss/resource-tool.scss', 'css')
