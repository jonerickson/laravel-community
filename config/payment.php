<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default PaymentProcessor Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment driver that will be used by
    | the PaymentManager. You may set this to any of the drivers defined
    | in the "drivers" array below.
    |
    */

    'default' => env('PAYMENT_DRIVER', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | PaymentProcessor Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment drivers for your application.
    | Each driver requires specific configuration options depending on the
    | payment provider being used.
    |
    */

    'drivers' => [
        'stripe' => [
            'driver' => App\Drivers\Payments\StripeDriver::class,
            'api_key' => env('STRIPE_SECRET'),
        ],

        // Add more payment drivers here as needed
        // 'paypal' => [
        //     'driver' => \App\Services\Drivers\PayPalDriver::class,
        //     'client_id' => env('PAYPAL_CLIENT_ID'),
        //     'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        //     'sandbox' => env('PAYPAL_SANDBOX', true),
        // ],
    ],
];
