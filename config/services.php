<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    /*
     * Менеджеры поддержки в Telegram (без @).
     * Можно переопределить через .env: SUPPORT_MANAGERS=manager1,manager2
     */
    'support' => [
        'managers' => array_values(array_filter(array_map('trim', explode(
            ',',
            env('SUPPORT_MANAGERS', 'alyans_manager1,alyans_manager2')
        )))),

        /*
         * Telegram-супергруппа с темами (forum) для менеджеров.
         * ID отрицательный (для супергрупп вида -100xxxxx).
         * Бот должен быть админом группы с правом "Управлять темами".
         */
        'group_id' => env('TELEGRAM_SUPPORT_GROUP_ID'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'onec' => [
        'token' => env('ONEC_TOKEN'),
    ],

    'sova' => [
        'base_url' => env('SOVA_API_URL', 'http://188.127.242.20:43425'),
//        'token' => env('SOVA_API_TOKEN'),
        'token' => 'cqlVLs3xBip$eFvVSW9G9LHay##mM17LZW2fVK5Jsb0=',
    ],

    'cdek' => [
        'client_id' => env('CDEK_CLIENT_ID'),
        'client_secret' => env('CDEK_CLIENT_SECRET'),
        'api_url' => env('CDEK_API_URL', 'https://api.cdek.ru/v2'),
        'from_city_code' => env('CDEK_FROM_CITY_CODE', 44),
    ],

    'yandex' => [
        'geocoder_key' => env('YANDEX_MAPS_API_KEY'),           // JavaScript API и HTTP Геокодер (UUID формат)
        'delivery_token' => env('YANDEX_DELIVERY_TOKEN'),       // Яндекс Доставка Express API (y0__ формат)
    ],

];
