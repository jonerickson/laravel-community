<?php

declare(strict_types=1);

use App\Data\PaymentErrorData;
use App\Drivers\Payments\Concerns\TracksErrors;
use App\Drivers\Payments\NullDriver;
use App\Drivers\Payments\StripeDriver;

describe('Payment processor error tracking', function (): void {
    describe('TracksErrors trait', function (): void {
        test('lastError defaults to null', function (): void {
            $driver = new class
            {
                use TracksErrors;
            };

            expect($driver->lastError)->toBeNull();
        });

        test('lastError can be set and retrieved', function (): void {
            $driver = new class
            {
                use TracksErrors;

                public function simulateError(): void
                {
                    $this->lastError = new PaymentErrorData(
                        method: 'createProduct',
                        message: 'No such product',
                        exceptionClass: Stripe\Exception\InvalidRequestException::class,
                        code: 'resource_missing',
                    );
                }
            };

            $driver->simulateError();

            expect($driver->lastError)
                ->toBeInstanceOf(PaymentErrorData::class)
                ->method->toBe('createProduct')
                ->message->toBe('No such product')
                ->exceptionClass->toBe(Stripe\Exception\InvalidRequestException::class)
                ->code->toBe('resource_missing');
        });

        test('lastError can be reset to null', function (): void {
            $driver = new class
            {
                use TracksErrors;

                public function simulateError(): void
                {
                    $this->lastError = new PaymentErrorData(
                        method: 'deleteProduct',
                        message: 'API error',
                        exceptionClass: 'Exception',
                    );
                }

                public function resetError(): void
                {
                    $this->lastError = null;
                }
            };

            $driver->simulateError();

            expect($driver->lastError)->not->toBeNull();

            $driver->resetError();
            expect($driver->lastError)->toBeNull();
        });

        test('code is optional and defaults to null', function (): void {
            $driver = new class
            {
                use TracksErrors;

                public function simulateError(): void
                {
                    $this->lastError = new PaymentErrorData(
                        method: 'syncCustomerInformation',
                        message: 'Connection timeout',
                        exceptionClass: 'Exception',
                    );
                }
            };

            $driver->simulateError();

            expect($driver->lastError->code)->toBeNull();
        });
    });

    describe('StripeDriver', function (): void {
        test('uses TracksErrors trait', function (): void {
            expect(class_uses_recursive(StripeDriver::class))
                ->toContain(TracksErrors::class);
        });

        test('lastError is null by default', function (): void {
            $driver = new StripeDriver(config('cashier.secret', 'sk_test_fake'));

            expect($driver->lastError)->toBeNull();
        });
    });

    describe('NullDriver', function (): void {
        test('uses TracksErrors trait', function (): void {
            expect(class_uses_recursive(NullDriver::class))
                ->toContain(TracksErrors::class);
        });

        test('lastError is null by default', function (): void {
            $driver = new NullDriver;

            expect($driver->lastError)->toBeNull();
        });
    });
});
