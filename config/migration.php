<?php

declare(strict_types=1);

return [
    'sources' => [
        'invision_community' => [
            'ssh' => [
                'host' => env('MIGRATION_IC_SSH_HOST'),
                'user' => env('MIGRATION_IC_SSH_USER'),
                'port' => env('MIGRATION_IC_SSH_PORT', 22),
                'key' => env('MIGRATION_IC_SSH_KEY'),
            ],
        ],
    ],
];
