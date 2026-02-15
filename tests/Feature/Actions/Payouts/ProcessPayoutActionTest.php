<?php

declare(strict_types=1);

use App\Actions\Payouts\ProcessPayoutAction;
use App\Data\PayoutData;
use App\Data\TransferData;
use App\Enums\PayoutStatus;
use App\Events\PayoutFailed;
use App\Events\PayoutProcessed;
use App\Exceptions\InvalidPayoutStatusException;
use App\Facades\PayoutProcessor;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('ProcessPayoutAction', function (): void {
    test('throws InvalidPayoutStatusException when payout is not pending', function (): void {
        $seller = User::factory()->create();
        $payout = Payout::factory()->completed()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new ProcessPayoutAction(payout: $payout);

        expect(fn (): bool => $action())->toThrow(
            InvalidPayoutStatusException::class,
            'A payout must be in pending status to be processed. Current status: completed'
        );
    });

    test('throws InvalidPayoutStatusException when payout is failed', function (): void {
        $seller = User::factory()->create();
        $payout = Payout::factory()->failed()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new ProcessPayoutAction(payout: $payout);

        expect(fn (): bool => $action())->toThrow(
            InvalidPayoutStatusException::class,
            'A payout must be in pending status to be processed. Current status: failed'
        );
    });

    test('throws InvalidPayoutStatusException when payout is cancelled', function (): void {
        $seller = User::factory()->create();
        $payout = Payout::factory()->cancelled()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new ProcessPayoutAction(payout: $payout);

        expect(fn (): bool => $action())->toThrow(
            InvalidPayoutStatusException::class,
            'A payout must be in pending status to be processed. Current status: cancelled'
        );
    });

    test('returns true and sets status to Completed when payout succeeds', function (): void {
        Event::fake([PayoutProcessed::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'amount' => 100.00,
        ]);

        $transferData = TransferData::from([
            'id' => 'tr_123',
            'amount' => 100.00,
            'currency' => 'usd',
            'destination' => 'acct_123',
            'reversed' => false,
        ]);

        $payoutData = PayoutData::from([
            'id' => $payout->id,
            'userId' => $seller->id,
            'amount' => 100.00,
            'status' => PayoutStatus::Completed,
        ]);

        PayoutProcessor::shouldReceive('createTransfer')
            ->once()
            ->andReturn($transferData);

        PayoutProcessor::shouldReceive('createPayout')
            ->once()
            ->andReturn($payoutData);

        $action = new ProcessPayoutAction(payout: $payout);
        $result = $action();

        expect($result)->toBeTrue();
        expect($payout->refresh()->status)->toBe(PayoutStatus::Completed);

        Event::assertDispatched(PayoutProcessed::class, fn ($event): bool => $event->payout->id === $payout->id);
    });

    test('dispatches PayoutProcessed event on success', function (): void {
        Event::fake([PayoutProcessed::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'amount' => 50.00,
        ]);

        PayoutProcessor::shouldReceive('createTransfer')
            ->once()
            ->andReturn(TransferData::from([
                'id' => 'tr_456',
                'amount' => 50.00,
                'currency' => 'usd',
                'destination' => 'acct_456',
                'reversed' => false,
            ]));

        PayoutProcessor::shouldReceive('createPayout')
            ->once()
            ->andReturn(PayoutData::from([
                'id' => 1,
                'userId' => 1,
                'amount' => 50.00,
                'status' => PayoutStatus::Completed,
            ]));

        $action = new ProcessPayoutAction(payout: $payout);
        $action();

        Event::assertDispatched(PayoutProcessed::class, fn ($event): bool => $event->payout->id === $payout->id);
    });

    test('returns false and sets status to Failed when transfer fails', function (): void {
        Event::fake([PayoutFailed::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'amount' => 100.00,
        ]);

        PayoutProcessor::shouldReceive('createTransfer')
            ->once()
            ->andReturn(null);

        $action = new ProcessPayoutAction(payout: $payout);
        $result = $action();

        expect($result)->toBeFalse();
        expect($payout->refresh()->status)->toBe(PayoutStatus::Failed);
        expect($payout->failure_reason)->toBe('Driver returned null - payout creation failed');

        Event::assertDispatched(PayoutFailed::class, fn ($event): bool => $event->payout->id === $payout->id && $event->reason === 'Driver error');
    });

    test('returns false and sets status to Failed when payout creation fails', function (): void {
        Event::fake([PayoutFailed::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'amount' => 100.00,
        ]);

        PayoutProcessor::shouldReceive('createTransfer')
            ->once()
            ->andReturn(TransferData::from([
                'id' => 'tr_789',
                'amount' => 100.00,
                'currency' => 'usd',
                'destination' => 'acct_789',
                'reversed' => false,
            ]));

        PayoutProcessor::shouldReceive('createPayout')
            ->once()
            ->andReturn(null);

        $action = new ProcessPayoutAction(payout: $payout);
        $result = $action();

        expect($result)->toBeFalse();
        expect($payout->refresh()->status)->toBe(PayoutStatus::Failed);
        expect($payout->failure_reason)->toBe('Driver returned null - payout creation failed');

        Event::assertDispatched(PayoutFailed::class);
    });

    test('returns false and sets status to Failed when exception is thrown', function (): void {
        Event::fake([PayoutFailed::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'amount' => 100.00,
        ]);

        PayoutProcessor::shouldReceive('createTransfer')
            ->once()
            ->andThrow(new Exception('Stripe API error'));

        $action = new ProcessPayoutAction(payout: $payout);
        $result = $action();

        expect($result)->toBeFalse();
        expect($payout->refresh()->status)->toBe(PayoutStatus::Failed);
        expect($payout->failure_reason)->toBe('Stripe API error');

        Event::assertDispatched(PayoutFailed::class, fn ($event): bool => $event->reason === 'Stripe API error');
    });

    test('dispatches PayoutFailed event with correct reason on failure', function (): void {
        Event::fake([PayoutFailed::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'amount' => 100.00,
        ]);

        PayoutProcessor::shouldReceive('createTransfer')
            ->once()
            ->andThrow(new Exception('Insufficient funds in connected account'));

        $action = new ProcessPayoutAction(payout: $payout);
        $action();

        Event::assertDispatched(PayoutFailed::class, fn ($event): bool => $event->payout->id === $payout->id
            && $event->reason === 'Insufficient funds in connected account');
    });

    test('can be executed via static execute method', function (): void {
        Event::fake([PayoutProcessed::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'amount' => 75.00,
        ]);

        PayoutProcessor::shouldReceive('createTransfer')
            ->once()
            ->andReturn(TransferData::from([
                'id' => 'tr_static',
                'amount' => 75.00,
                'currency' => 'usd',
                'destination' => 'acct_static',
                'reversed' => false,
            ]));

        PayoutProcessor::shouldReceive('createPayout')
            ->once()
            ->andReturn(PayoutData::from([
                'id' => 1,
                'userId' => 1,
                'amount' => 75.00,
                'status' => PayoutStatus::Completed,
            ]));

        $result = ProcessPayoutAction::execute($payout);

        expect($result)->toBeTrue();
        expect($payout->refresh()->status)->toBe(PayoutStatus::Completed);
    });

    test('uses transaction to ensure atomicity', function (): void {
        Event::fake([PayoutProcessed::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'amount' => 200.00,
        ]);

        PayoutProcessor::shouldReceive('createTransfer')
            ->once()
            ->andReturn(TransferData::from([
                'id' => 'tr_txn',
                'amount' => 200.00,
                'currency' => 'usd',
                'destination' => 'acct_txn',
                'reversed' => false,
            ]));

        PayoutProcessor::shouldReceive('createPayout')
            ->once()
            ->andReturn(PayoutData::from([
                'id' => 1,
                'userId' => 1,
                'amount' => 200.00,
                'status' => PayoutStatus::Completed,
            ]));

        $action = new ProcessPayoutAction(payout: $payout);
        $result = $action();

        expect($result)->toBeTrue();
        expect($payout->refresh()->status)->toBe(PayoutStatus::Completed);
    });
});
