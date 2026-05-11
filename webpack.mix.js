const mix = require('laravel-mix');

mix.options({
    cache: true
});

// mix.js('resources/js/main.js', 'public/site_js')
//     .js('resources/js/swiper.js', 'public/site_js')
//     .css('resources/css/auth.css', 'public/css')
//     .css('resources/css/book-info.css', 'public/css')
//     .css('resources/css/contacts.css', 'public/css')
//     .css('resources/css/genres-book.css', 'public/css')
//     .css('resources/css/header.css', 'public/css')
//     .css('resources/css/main-content.css', 'public/css')
//     .css('resources/css/room.css', 'public/css')
//     .css('resources/css/style.css', 'public/css')
//     .version()
//     .minify('public/css/auth.css')
//     .minify('public/css/book-info.css')
//     .minify('public/css/contacts.css')
//     .minify('public/css/genres-book.css')
//     .minify('public/css/header.css')
//     .minify('public/css/main-content.css')
//     .minify('public/css/room.css')
//     .minify('public/css/style.css');
