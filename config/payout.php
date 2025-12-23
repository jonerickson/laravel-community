<?php

declare(strict_types=1);

use Stripe\Account;

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
        'connect_type' => env('STRIPE_CONNECT_TYPE', Account::TYPE_EXPRESS),
    ],

    'minimum_payout' => (float) env('PAYOUT_MINIMUM_AMOUNT', 10.00),
    'maximum_payout' => (float) env('PAYOUT_MAXIMUM_AMOUNT', 10000.00),

    'statement_descriptor' => env('PAYOUT_STATEMENT_DESCRIPTOR', env('APP_NAME')),
];
