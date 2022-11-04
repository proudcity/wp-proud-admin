// webpack.mix.js

let mix = require('laravel-mix');

mix.webpackConfig({
    devtool: "source-map"
});

mix.sass('assets/styles/proud-admin.scss', 'dist/styles', {
    sassOptions:{
        outputStyle: "compressed",
        includePaths: [
            'bower_components/bootstrap-sass-official/assets/stylesheets',
            'bower_components/bourbon/dist',
            'bower_components/proudcity-patterns/app'
        ],
    }
})
    .sourceMaps();