<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Support Ticket Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default support ticket driver that will be used
    | on requests. You may set this to any of the drivers defined in the
    | "drivers" array below.
    |
    | Supported: "database", "zendesk"
    |
    */

    'default' => env('SUPPORT_TICKET_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Support Ticket Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the drivers for the support ticket system.
    | Each driver has different configuration options.
    |
    */

    'drivers' => [
        'database' => [
            'driver' => 'database',
        ],

        'zendesk' => [
            'driver' => 'zendesk',
            'subdomain' => env('ZENDESK_SUBDOMAIN'),
            'email' => env('ZENDESK_EMAIL'),
            'token' => env('ZENDESK_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Category
    |--------------------------------------------------------------------------
    |
    | The default category ID to assign to tickets when none is specified.
    |
    */

    'default_category_id' => env('SUPPORT_TICKET_DEFAULT_CATEGORY', 1),

    /*
    |--------------------------------------------------------------------------
    | Auto Assignment
    |--------------------------------------------------------------------------
    |
    | Whether to automatically assign tickets based on category settings.
    |
    */

    'auto_assign' => env('SUPPORT_TICKET_AUTO_ASSIGN', false),

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for syncing with external ticket systems.
    |
    */

    'sync' => [
        'enabled' => env('SUPPORT_TICKET_SYNC_ENABLED', true),
        'frequency' => env('SUPPORT_TICKET_SYNC_FREQUENCY', 60), // minutes
        'batch_size' => env('SUPPORT_TICKET_SYNC_BATCH_SIZE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for email notifications when tickets are created or updated.
    |
    */

    'notifications' => [
        'enabled' => env('SUPPORT_TICKET_NOTIFICATIONS_ENABLED', true),
        'notify_admins' => env('SUPPORT_TICKET_NOTIFY_ADMINS', true),
        'notify_assignee' => env('SUPPORT_TICKET_NOTIFY_ASSIGNEE', true),
        'notify_author' => env('SUPPORT_TICKET_NOTIFY_AUTHOR', true),
    ],
];
