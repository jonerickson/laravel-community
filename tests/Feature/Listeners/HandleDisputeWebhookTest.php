<?php

declare(strict_types=1);

use App\Drivers\Payments\PaymentProcessor;
use App\Enums\DisputeAction;
use App\Enums\DisputeStatus;
use App\Events\DisputeClosed;
use App\Events\DisputeCreated;
use App\Events\DisputeUpdated;
use App\Listeners\HandleDisputeCreated;
use App\Listeners\Stripe\HandleWebhook;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Settings\DisputeSettings;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookReceived;

function createDisputePayload(string $type, array $disputeData, ?string $eventId = null): array
{
    return [
        'id' => $eventId ?? 'evt_'.fake()->regexify('[A-Za-z0-9]{24}'),
        'type' => $type,
        'data' => [
            'object' => array_merge([
                'id' => 'dp_'.fake()->regexify('[A-Za-z0-9]{24}'),
                'charge' => 'ch_'.fake()->regexify('[A-Za-z0-9]{24}'),
                'payment_intent' => 'pi_'.fake()->regexify('[A-Za-z0-9]{24}'),
                'customer' => 'cus_test123',
                'status' => 'needs_response',
                'reason' => 'fraudulent',
                'amount' => 5000,
                'currency' => 'usd',
                'evidence_details' => ['due_by' => now()->addDays(21)->timestamp],
                'is_charge_refundable' => true,
                'network_reason_code' => '4837',
                'metadata' => [],
            ], $disputeData),
        ],
    ];
}

describe('charge.dispute.created webhook', function (): void {
    test('creates a dispute record linked to the correct order and user', function (): void {
        Event::fake([DisputeCreated::class]);

        $user = User::factory()->create(['stripe_id' => 'cus_test123']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'external_order_id' => 'pi_matched123',
        ]);

        $payload = createDisputePayload('charge.dispute.created', [
            'id' => 'dp_test_dispute_1',
            'charge' => 'ch_test_charge_1',
            'payment_intent' => 'pi_matched123',
            'customer' => 'cus_test123',
        ]);

        $listener = app(HandleWebhook::class);
        $listener->handle(new WebhookReceived($payload));

        $dispute = Dispute::where('external_dispute_id', 'dp_test_dispute_1')->first();

        expect($dispute)->not->toBeNull()
            ->and($dispute->user_id)->toBe($user->id)
            ->and($dispute->order_id)->toBe($order->id)
            ->and($dispute->external_charge_id)->toBe('ch_test_charge_1')
            ->and($dispute->external_payment_intent_id)->toBe('pi_matched123')
            ->and($dispute->status)->toBe(DisputeStatus::NeedsResponse)
            ->and($dispute->amount)->toBe(5000)
            ->and($dispute->currency)->toBe('usd');

        Event::assertDispatched(DisputeCreated::class, fn (DisputeCreated $event): bool => $event->dispute->id === $dispute->id);
    });

    test('does not create a dispute when order cannot be resolved', function (): void {
        Event::fake([DisputeCreated::class]);

        $user = User::factory()->create(['stripe_id' => 'cus_no_order']);

        $payload = createDisputePayload('charge.dispute.created', [
            'payment_intent' => 'pi_nonexistent',
            'customer' => 'cus_no_order',
            'metadata' => [],
        ]);

        $listener = app(HandleWebhook::class);
        $listener->handle(new WebhookReceived($payload));

        expect(Dispute::count())->toBe(0);
        Event::assertNotDispatched(DisputeCreated::class);
    });
});

describe('charge.dispute.updated webhook', function (): void {
    test('updates the existing dispute status', function (): void {
        Event::fake([DisputeUpdated::class]);

        $user = User::factory()->create(['stripe_id' => 'cus_update_test']);
        $order = Order::factory()->create(['user_id' => $user->id, 'external_order_id' => 'pi_update']);
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'external_dispute_id' => 'dp_update_test',
            'status' => DisputeStatus::NeedsResponse,
        ]);

        $payload = createDisputePayload('charge.dispute.updated', [
            'id' => 'dp_update_test',
            'payment_intent' => 'pi_update',
            'customer' => 'cus_update_test',
            'status' => 'under_review',
        ]);

        $listener = app(HandleWebhook::class);
        $listener->handle(new WebhookReceived($payload));

        $dispute->refresh();

        expect($dispute->status)->toBe(DisputeStatus::UnderReview);
        Event::assertDispatched(DisputeUpdated::class);
    });
});

describe('charge.dispute.closed webhook', function (): void {
    test('updates dispute status to won', function (): void {
        Event::fake([DisputeClosed::class]);

        $user = User::factory()->create(['stripe_id' => 'cus_close_test']);
        $order = Order::factory()->create(['user_id' => $user->id, 'external_order_id' => 'pi_close']);
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'external_dispute_id' => 'dp_close_test',
            'status' => DisputeStatus::UnderReview,
        ]);

        $payload = createDisputePayload('charge.dispute.closed', [
            'id' => 'dp_close_test',
            'payment_intent' => 'pi_close',
            'customer' => 'cus_close_test',
            'status' => 'won',
        ]);

        $listener = app(HandleWebhook::class);
        $listener->handle(new WebhookReceived($payload));

        $dispute->refresh();

        expect($dispute->status)->toBe(DisputeStatus::Won);
        Event::assertDispatched(DisputeClosed::class);
    });

    test('updates dispute status to lost', function (): void {
        Event::fake([DisputeClosed::class]);

        $user = User::factory()->create(['stripe_id' => 'cus_lost_test']);
        $order = Order::factory()->create(['user_id' => $user->id, 'external_order_id' => 'pi_lost']);
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'external_dispute_id' => 'dp_lost_test',
            'status' => DisputeStatus::UnderReview,
        ]);

        $payload = createDisputePayload('charge.dispute.closed', [
            'id' => 'dp_lost_test',
            'payment_intent' => 'pi_lost',
            'customer' => 'cus_lost_test',
            'status' => 'lost',
        ]);

        $listener = app(HandleWebhook::class);
        $listener->handle(new WebhookReceived($payload));

        $dispute->refresh();

        expect($dispute->status)->toBe(DisputeStatus::Lost);
        Event::assertDispatched(DisputeClosed::class);
    });
});

describe('HandleDisputeCreated automated actions', function (): void {
    test('blacklists user when BlacklistUser action is configured', function (): void {
        $user = User::factory()->create(['stripe_id' => 'cus_blacklist_test']);
        $order = Order::factory()->create(['user_id' => $user->id]);
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => DisputeStatus::NeedsResponse,
        ]);

        $settings = app(DisputeSettings::class);
        $settings->dispute_actions = [DisputeAction::BlacklistUser->value];
        $settings->save();

        $listener = app(HandleDisputeCreated::class);
        $listener->handle(new DisputeCreated($dispute));

        $user->refresh();
        expect($user->is_blacklisted)->toBeTrue();
    });

    test('calls PaymentProcessor cancelSubscription when configured', function (): void {
        $mock = Mockery::mock(PaymentProcessor::class)->shouldIgnoreMissing();
        $mock->shouldReceive('cancelSubscription')->once()->andReturnTrue();
        app()->instance('payment-processor', $mock);

        $user = User::factory()->create(['stripe_id' => 'cus_cancel_test']);
        $order = Order::factory()->create(['user_id' => $user->id]);
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => DisputeStatus::NeedsResponse,
        ]);

        $settings = app(DisputeSettings::class);
        $settings->dispute_actions = [DisputeAction::CancelSubscription->value];
        $settings->save();

        $listener = app(HandleDisputeCreated::class);
        $listener->handle(new DisputeCreated($dispute));
    });

    test('Nothing action does not blacklist or cancel anything', function (): void {
        $user = User::factory()->create(['stripe_id' => 'cus_nothing_test']);
        $order = Order::factory()->create(['user_id' => $user->id]);
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => DisputeStatus::NeedsResponse,
        ]);

        $settings = app(DisputeSettings::class);
        $settings->dispute_actions = [DisputeAction::Nothing->value];
        $settings->save();

        $listener = app(HandleDisputeCreated::class);
        $listener->handle(new DisputeCreated($dispute));

        $user->refresh();
        expect($user->is_blacklisted)->toBeFalse();
    });

    test('multiple actions can run together', function (): void {
        $mock = Mockery::mock(PaymentProcessor::class)->shouldIgnoreMissing();
        $mock->shouldReceive('cancelSubscription')->once()->andReturnTrue();
        app()->instance('payment-processor', $mock);

        $user = User::factory()->create(['stripe_id' => 'cus_multi_test']);
        $order = Order::factory()->create(['user_id' => $user->id]);
        $dispute = Dispute::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => DisputeStatus::NeedsResponse,
        ]);

        $settings = app(DisputeSettings::class);
        $settings->dispute_actions = [
            DisputeAction::BlacklistUser->value,
            DisputeAction::CancelSubscription->value,
        ];
        $settings->save();

        $listener = app(HandleDisputeCreated::class);
        $listener->handle(new DisputeCreated($dispute));

        $user->refresh();
        expect($user->is_blacklisted)->toBeTrue();
    });
});
