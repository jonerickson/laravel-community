<?php

declare(strict_types=1);

use App\Actions\Payouts\CancelPayoutAction;
use App\Enums\PayoutStatus;
use App\Events\PayoutCancelled;
use App\Exceptions\InvalidPayoutStatusException;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('CancelPayoutAction', function (): void {
    test('throws InvalidPayoutStatusException when payout is completed', function (): void {
        $seller = User::factory()->create();
        $payout = Payout::factory()->completed()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new CancelPayoutAction(payout: $payout);

        expect(fn (): bool => $action())->toThrow(
            InvalidPayoutStatusException::class,
            'The payout cannot be cancelled. Only pending payouts can be cancelled. Current status: completed'
        );
    });

    test('throws InvalidPayoutStatusException when payout is failed', function (): void {
        $seller = User::factory()->create();
        $payout = Payout::factory()->failed()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new CancelPayoutAction(payout: $payout);

        expect(fn (): bool => $action())->toThrow(
            InvalidPayoutStatusException::class,
            'The payout cannot be cancelled. Only pending payouts can be cancelled. Current status: failed'
        );
    });

    test('throws InvalidPayoutStatusException when payout is already cancelled', function (): void {
        $seller = User::factory()->create();
        $payout = Payout::factory()->cancelled()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new CancelPayoutAction(payout: $payout);

        expect(fn (): bool => $action())->toThrow(
            InvalidPayoutStatusException::class,
            'The payout cannot be cancelled. Only pending payouts can be cancelled. Current status: cancelled'
        );
    });

    test('returns true and sets status to Cancelled when payout is pending', function (): void {
        Event::fake([PayoutCancelled::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new CancelPayoutAction(payout: $payout);
        $result = $action();

        expect($result)->toBeTrue();
        expect($payout->refresh()->status)->toBe(PayoutStatus::Cancelled);

        Event::assertDispatched(PayoutCancelled::class, fn ($event): bool => $event->payout->id === $payout->id);
    });

    test('dispatches PayoutCancelled event on success', function (): void {
        Event::fake([PayoutCancelled::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new CancelPayoutAction(payout: $payout);
        $action();

        Event::assertDispatched(PayoutCancelled::class, fn ($event): bool => $event->payout->id === $payout->id);
    });

    test('appends cancellation reason to notes when provided', function (): void {
        Event::fake([PayoutCancelled::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'notes' => 'Original notes',
        ]);

        $action = new CancelPayoutAction(
            payout: $payout,
            reason: 'Customer requested cancellation'
        );
        $result = $action();

        expect($result)->toBeTrue();
        expect($payout->refresh()->notes)->toContain('Original notes');
        expect($payout->notes)->toContain('Cancellation reason: Customer requested cancellation');
    });

    test('handles null notes when appending reason', function (): void {
        Event::fake([PayoutCancelled::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'notes' => null,
        ]);

        $action = new CancelPayoutAction(
            payout: $payout,
            reason: 'Duplicate payout'
        );
        $result = $action();

        expect($result)->toBeTrue();
        expect($payout->refresh()->notes)->toContain('Cancellation reason: Duplicate payout');
    });

    test('does not append reason text when reason is null', function (): void {
        Event::fake([PayoutCancelled::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'notes' => 'Existing notes',
        ]);

        $action = new CancelPayoutAction(payout: $payout);
        $result = $action();

        expect($result)->toBeTrue();
        expect($payout->refresh()->notes)->toBe('Existing notes');
        expect($payout->notes)->not->toContain('Cancellation reason');
    });

    test('dispatches PayoutCancelled event with reason', function (): void {
        Event::fake([PayoutCancelled::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new CancelPayoutAction(
            payout: $payout,
            reason: 'Seller account closed'
        );
        $action();

        Event::assertDispatched(PayoutCancelled::class, fn ($event): bool => $event->payout->id === $payout->id
            && $event->reason === 'Seller account closed');
    });

    test('dispatches PayoutCancelled event with null reason when not provided', function (): void {
        Event::fake([PayoutCancelled::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
        ]);

        $action = new CancelPayoutAction(payout: $payout);
        $action();

        Event::assertDispatched(PayoutCancelled::class, fn ($event): bool => $event->payout->id === $payout->id
            && $event->reason === null);
    });

    test('can be executed via static execute method', function (): void {
        Event::fake([PayoutCancelled::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
        ]);

        $result = CancelPayoutAction::execute($payout, 'Static method test');

        expect($result)->toBeTrue();
        expect($payout->refresh()->status)->toBe(PayoutStatus::Cancelled);
        expect($payout->notes)->toContain('Cancellation reason: Static method test');
    });

    test('can be executed via static execute method without reason', function (): void {
        Event::fake([PayoutCancelled::class]);

        $seller = User::factory()->create();
        $payout = Payout::factory()->pending()->create([
            'seller_id' => $seller->id,
            'notes' => 'Test notes',
        ]);

        $result = CancelPayoutAction::execute($payout);

        expect($result)->toBeTrue();
        expect($payout->refresh()->status)->toBe(PayoutStatus::Cancelled);
        expect($payout->notes)->toBe('Test notes');
    });
});
