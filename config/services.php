<?php

declare(strict_types=1);

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

    'cloudflare' => [
        'api_key' => getenv('CF_CACHE_API_TOKEN'),
        'zone_id' => getenv('CF_CACHE_ZONE_ID'),
    ],

    'discord' => [
        'enabled' => env('DISCORD_ENABLED', false),
        'client_id' => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect' => env('DISCORD_REDIRECT_URI'),
        'guild_id' => env('DISCORD_GUILD_ID'),
        'bot_token' => env('DISCORD_BOT_TOKEN'),
    ],

    'fingerprint' => [
        'endpoint' => env('FINGERPRINT_ENDPONT'),
        'api_key' => env('FINGERPRINT_API_KEY'),
        'suspect_score_threshold' => env('FINGERPRINT_SUSPECT_THRESHOLD', 25),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'roblox' => [
        'api_key' => env('ROBLOX_API_KEY'),
        'group_id' => env('ROBLOX_GROUP_ID'),
        'enabled' => env('ROBLOX_ENABLED', false),
        'client_id' => env('ROBLOX_CLIENT_ID'),
        'client_secret' => env('ROBLOX_CLIENT_SECRET'),
        'redirect' => env('ROBLOX_REDIRECT_URI'),
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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'workos' => [
        'client_id' => env('WORKOS_CLIENT_ID'),
        'secret' => env('WORKOS_API_KEY'),
        'redirect_url' => env('WORKOS_REDIRECT_URL'),
    ],
];
