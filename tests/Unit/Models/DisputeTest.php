<?php

declare(strict_types=1);

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('Dispute factory', function (): void {
    test('can be created using factory with valid data', function (): void {
        $dispute = Dispute::factory()->create();

        expect($dispute)->toBeInstanceOf(Dispute::class)
            ->and($dispute->exists)->toBeTrue()
            ->and($dispute->external_dispute_id)->toStartWith('dp_')
            ->and($dispute->external_charge_id)->toStartWith('ch_')
            ->and($dispute->status)->toBeInstanceOf(DisputeStatus::class)
            ->and($dispute->reason)->toBeInstanceOf(DisputeReason::class)
            ->and($dispute->amount)->toBeInt()
            ->and($dispute->currency)->toBe('usd');
    });
});

describe('Dispute user relationship', function (): void {
    test('belongs to a user', function (): void {
        $user = User::factory()->create();
        $dispute = Dispute::factory()->create(['user_id' => $user->id]);

        expect($dispute->user->id)->toBe($user->id);
    });

    test('user relationship is BelongsTo', function (): void {
        $dispute = Dispute::factory()->create();

        expect($dispute->user())->toBeInstanceOf(BelongsTo::class);
    });
});

describe('Dispute order relationship', function (): void {
    test('belongs to an order', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $dispute = Dispute::factory()->create(['user_id' => $user->id, 'order_id' => $order->id]);

        expect($dispute->order->id)->toBe($order->id);
    });

    test('order relationship is BelongsTo', function (): void {
        $dispute = Dispute::factory()->create();

        expect($dispute->order())->toBeInstanceOf(BelongsTo::class);
    });
});

describe('Order disputes relationship', function (): void {
    test('order has many disputes', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        Dispute::factory()->count(3)->create(['user_id' => $user->id, 'order_id' => $order->id]);

        expect($order->disputes)->toHaveCount(3);
    });

    test('disputes relationship is HasMany', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        expect($order->disputes())->toBeInstanceOf(HasMany::class);
    });
});

describe('User disputes relationship', function (): void {
    test('user has many disputes', function (): void {
        $user = User::factory()->create();
        Dispute::factory()->count(2)->create(['user_id' => $user->id]);

        expect($user->disputes)->toHaveCount(2);
    });

    test('disputes relationship is HasMany', function (): void {
        $user = User::factory()->create();

        expect($user->disputes())->toBeInstanceOf(HasMany::class);
    });
});

describe('DisputeStatus enum', function (): void {
    test('has correct labels', function (): void {
        expect(DisputeStatus::NeedsResponse->getLabel())->toBe('Needs Response')
            ->and(DisputeStatus::Won->getLabel())->toBe('Won')
            ->and(DisputeStatus::Lost->getLabel())->toBe('Lost')
            ->and(DisputeStatus::UnderReview->getLabel())->toBe('Under Review')
            ->and(DisputeStatus::ChargeRefunded->getLabel())->toBe('Charge Refunded')
            ->and(DisputeStatus::WarningNeedsResponse->getLabel())->toBe('Warning Needs Response');
    });

    test('has correct colors', function (): void {
        expect(DisputeStatus::NeedsResponse->getColor())->toBe('danger')
            ->and(DisputeStatus::Lost->getColor())->toBe('danger')
            ->and(DisputeStatus::Won->getColor())->toBe('success')
            ->and(DisputeStatus::UnderReview->getColor())->toBe('warning')
            ->and(DisputeStatus::WarningNeedsResponse->getColor())->toBe('warning')
            ->and(DisputeStatus::WarningClosed->getColor())->toBe('gray')
            ->and(DisputeStatus::ChargeRefunded->getColor())->toBe('gray');
    });
});

describe('DisputeReason enum', function (): void {
    test('has correct labels', function (): void {
        expect(DisputeReason::Fraudulent->getLabel())->toBe('Fraudulent')
            ->and(DisputeReason::ProductNotReceived->getLabel())->toBe('Product Not Received')
            ->and(DisputeReason::Duplicate->getLabel())->toBe('Duplicate')
            ->and(DisputeReason::SubscriptionCanceled->getLabel())->toBe('Subscription Canceled')
            ->and(DisputeReason::BankCannotProcess->getLabel())->toBe('Bank Cannot Process');
    });
});
