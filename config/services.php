<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Drive (evidencias de tickets)
    |--------------------------------------------------------------------------
    |
    | Se obtiene el refresh token con "php artisan drive:auth". El scope es
    | drive.file: la app solo ve los archivos que ella misma sube.
    |
    */

    'google_drive' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI', 'http://localhost:8000/oauth/drive/callback'),
        'scope' => env('GOOGLE_DRIVE_SCOPE', 'https://www.googleapis.com/auth/drive.file'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nagios Core (estado.php)
    |--------------------------------------------------------------------------
    */

    'nagios' => [
        'url' => env('NAGIOS_URL'),
        'user' => env('NAGIOS_USER'),
        'pass' => env('NAGIOS_PASS'),
    ],

];
