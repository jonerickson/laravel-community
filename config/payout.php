<?php

declare(strict_types=1);

return [
    'default' => env('PAYOUT_DRIVER', 'stripe'),

    'drivers' => [
        'null' => [
            'driver' => App\Drivers\Payouts\NullDriver::class,
        ],

        'stripe' => [
            'driver' => App\Drivers\Payouts\StripeDriver::class,
        ],
    ],

    'stripe' => [
        'connect_type' => env('STRIPE_CONNECT_TYPE', 'express'),
        'onboarding_return_url' => env('STRIPE_ONBOARDING_RETURN_URL'),
        'onboarding_refresh_url' => env('STRIPE_ONBOARDING_REFRESH_URL'),
    ],

    'minimum_payout' => (float) env('MINIMUM_PAYOUT_AMOUNT', 10.00),
    'maximum_payout' => (float) env('MAXIMUM_PAYOUT_AMOUNT', 10000.00),
];
