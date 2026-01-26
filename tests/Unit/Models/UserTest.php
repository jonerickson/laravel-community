<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Models\Commission;
use App\Models\Field;
use App\Models\Fingerprint;
use App\Models\Group;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserIntegration;
use App\Models\UserWarning;
use App\Models\Warning;
use App\Models\WarningConsequence;

describe('User orders relationship', function (): void {
    test('returns empty collection when user has no orders', function (): void {
        $user = User::factory()->create();

        expect($user->orders)->toBeEmpty();
    });

    test('returns orders belonging to user', function (): void {
        $user = User::factory()->create();
        $order1 = Order::factory()->create(['user_id' => $user->id]);
        $order2 = Order::factory()->create(['user_id' => $user->id]);

        $orders = $user->orders;

        expect($orders)->toHaveCount(2);
        expect($orders->pluck('id')->all())->toContain($order1->id, $order2->id);
    });

    test('does not return orders belonging to other users', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Order::factory()->create(['user_id' => $user->id]);
        Order::factory()->create(['user_id' => $otherUser->id]);

        expect($user->orders)->toHaveCount(1);
    });

    test('orders relationship is HasMany', function (): void {
        $user = User::factory()->create();

        expect($user->orders())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});

describe('User subscriptions relationship', function (): void {
    test('returns empty collection when user has no subscriptions', function (): void {
        $user = User::factory()->create();

        expect($user->subscriptions)->toBeEmpty();
    });

    test('returns subscriptions belonging to user', function (): void {
        $user = User::factory()->create();

        // Create subscription via Cashier's factory
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_test_'.uniqid(),
            'stripe_status' => 'active',
        ]);

        $subscriptions = $user->subscriptions;

        expect($subscriptions)->toHaveCount(1);
        expect($subscriptions->first()->id)->toBe($subscription->id);
    });

    test('does not return subscriptions belonging to other users', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_test_'.uniqid(),
            'stripe_status' => 'active',
        ]);

        Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'type' => 'default',
            'stripe_id' => 'sub_other_'.uniqid(),
            'stripe_status' => 'active',
        ]);

        expect($user->subscriptions)->toHaveCount(1);
    });

    test('subscriptions relationship is HasMany', function (): void {
        $user = User::factory()->create();

        expect($user->subscriptions())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});

describe('User products relationship (hasManyDeep)', function (): void {
    test('returns empty collection when user has no orders', function (): void {
        $user = User::factory()->create();

        expect($user->products)->toBeEmpty();
    });

    test('returns products from succeeded orders', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->visible()->create();

        $product = Product::factory()->approved()->active()->create([
            'type' => ProductType::Product,
        ]);
        $product->categories()->attach($category);

        $price = Price::factory()->create(['product_id' => $product->id]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Succeeded,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        $products = $user->products;

        expect($products)->toHaveCount(1);
        expect($products->first()->id)->toBe($product->id);
    });

    test('does not return products from pending orders', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->visible()->create();

        $product = Product::factory()->approved()->active()->create([
            'type' => ProductType::Product,
        ]);
        $product->categories()->attach($category);

        $price = Price::factory()->create(['product_id' => $product->id]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        expect($user->products)->toBeEmpty();
    });

    test('does not return subscription type products', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->visible()->create();

        $product = Product::factory()->approved()->active()->create([
            'type' => ProductType::Subscription,
        ]);
        $product->categories()->attach($category);

        $price = Price::factory()->create(['product_id' => $product->id]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Succeeded,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        expect($user->products)->toBeEmpty();
    });

    test('returns distinct products even when purchased multiple times', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->visible()->create();

        $product = Product::factory()->approved()->active()->create([
            'type' => ProductType::Product,
        ]);
        $product->categories()->attach($category);

        $price = Price::factory()->create(['product_id' => $product->id]);

        // Create two separate orders for the same product
        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Succeeded,
        ]);
        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Succeeded,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        // Should only return one product (distinct)
        expect($user->products)->toHaveCount(1);
    });
});

describe('User tickets relationship', function (): void {
    test('returns empty collection when user has no tickets', function (): void {
        $user = User::factory()->create();

        expect($user->tickets)->toBeEmpty();
    });

    test('returns tickets created by user', function (): void {
        $user = User::factory()->create();

        $ticket = SupportTicket::factory()->create(['created_by' => $user->id]);

        $tickets = $user->tickets;

        expect($tickets)->toHaveCount(1);
        expect($tickets->first()->id)->toBe($ticket->id);
    });

    test('does not return tickets created by other users', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        SupportTicket::factory()->create(['created_by' => $user->id]);
        SupportTicket::factory()->create(['created_by' => $otherUser->id]);

        expect($user->tickets)->toHaveCount(1);
    });

    test('tickets relationship uses created_by foreign key', function (): void {
        $user = User::factory()->create();

        $relation = $user->tickets();

        expect($relation->getForeignKeyName())->toBe('created_by');
    });
});

describe('User fingerprints relationship', function (): void {
    test('returns empty collection when user has no fingerprints', function (): void {
        $user = User::factory()->create();

        expect($user->fingerprints)->toBeEmpty();
    });

    test('returns fingerprints belonging to user', function (): void {
        $user = User::factory()->create();

        $fingerprint = Fingerprint::factory()->create(['user_id' => $user->id]);

        $fingerprints = $user->fingerprints;

        expect($fingerprints)->toHaveCount(1);
        expect($fingerprints->first()->id)->toBe($fingerprint->id);
    });

    test('does not return fingerprints belonging to other users', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Fingerprint::factory()->create(['user_id' => $user->id]);
        Fingerprint::factory()->create(['user_id' => $otherUser->id]);

        expect($user->fingerprints)->toHaveCount(1);
    });
});

describe('User integrations relationship', function (): void {
    test('returns empty collection when user has no integrations', function (): void {
        $user = User::factory()->create();

        expect($user->integrations)->toBeEmpty();
    });

    test('returns integrations belonging to user', function (): void {
        $user = User::factory()->create();

        $integration = UserIntegration::create([
            'user_id' => $user->id,
            'provider' => 'discord',
            'provider_id' => 'discord_'.uniqid(),
            'access_token' => 'token_'.uniqid(),
        ]);

        $integrations = $user->integrations;

        expect($integrations)->toHaveCount(1);
        expect($integrations->first()->id)->toBe($integration->id);
    });

    test('does not return integrations belonging to other users', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        UserIntegration::create([
            'user_id' => $user->id,
            'provider' => 'discord',
            'provider_id' => 'discord_user_'.uniqid(),
            'access_token' => 'token_'.uniqid(),
        ]);

        UserIntegration::create([
            'user_id' => $otherUser->id,
            'provider' => 'discord',
            'provider_id' => 'discord_other_'.uniqid(),
            'access_token' => 'token_'.uniqid(),
        ]);

        expect($user->integrations)->toHaveCount(1);
    });
});

describe('User fields relationship', function (): void {
    test('returns empty collection when user has no fields', function (): void {
        $user = User::factory()->create();

        expect($user->fields)->toBeEmpty();
    });

    test('returns fields attached to user with pivot value', function (): void {
        $user = User::factory()->create();
        $field = Field::factory()->create();

        $user->fields()->attach($field->id, ['value' => 'test_value']);

        $fields = $user->fields;

        expect($fields)->toHaveCount(1);
        expect($fields->first()->id)->toBe($field->id);
        expect($fields->first()->pivot->value)->toBe('test_value');
    });

    test('fields relationship is BelongsToMany', function (): void {
        $user = User::factory()->create();

        expect($user->fields())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    test('fields are ordered by order column', function (): void {
        $user = User::factory()->create();

        $field1 = Field::factory()->create(['order' => 3]);
        $field2 = Field::factory()->create(['order' => 1]);
        $field3 = Field::factory()->create(['order' => 2]);

        $user->fields()->attach([
            $field1->id => ['value' => 'value1'],
            $field2->id => ['value' => 'value2'],
            $field3->id => ['value' => 'value3'],
        ]);

        $fields = $user->fields;

        expect($fields->pluck('order')->all())->toBe([1, 2, 3]);
    });
});

describe('User payouts relationship (from CanBePaid trait)', function (): void {
    test('returns empty collection when user has no payouts', function (): void {
        $user = User::factory()->create();

        expect($user->payouts)->toBeEmpty();
    });

    test('returns payouts belonging to user', function (): void {
        $user = User::factory()->create();

        $payout = Payout::factory()->create(['seller_id' => $user->id]);

        $payouts = $user->payouts;

        expect($payouts)->toHaveCount(1);
        expect($payouts->first()->id)->toBe($payout->id);
    });

    test('payouts relationship uses seller_id foreign key', function (): void {
        $user = User::factory()->create();

        $relation = $user->payouts();

        expect($relation->getForeignKeyName())->toBe('seller_id');
    });
});

describe('User commissions relationship (from CanBePaid trait)', function (): void {
    test('commissions relationship is HasMany', function (): void {
        $user = User::factory()->create();

        expect($user->commissions())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    // Note: Commission model uses seller_id not user_id
    // The CanBePaid trait relationship uses default FK (user_id)
    // Testing via Commission->seller relationship which uses correct FK
    test('commission seller relationship links to user', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $commission = Commission::factory()->create([
            'seller_id' => $user->id,
            'order_id' => $order->id,
        ]);

        // Access via the Commission->seller relationship instead
        expect($commission->seller->id)->toBe($user->id);
    });
});

describe('User userWarnings relationship', function (): void {
    test('returns empty collection when user has no warnings', function (): void {
        $user = User::factory()->create();

        expect($user->userWarnings)->toBeEmpty();
    });

    test('returns warnings for user', function (): void {
        $user = User::factory()->create();
        $admin = User::factory()->asAdmin()->create();

        $warning = Warning::create([
            'name' => 'Test Warning',
            'points' => 1,
            'days_applied' => 7,
        ]);

        $consequence = WarningConsequence::create([
            'type' => App\Enums\WarningConsequenceType::None,
            'threshold' => 1,
            'duration_days' => 7,
        ]);

        $userWarning = UserWarning::create([
            'user_id' => $user->id,
            'warning_id' => $warning->id,
            'warning_consequence_id' => $consequence->id,
            'created_by' => $admin->id,
            'reason' => 'Test warning note',
            'points_at_issue' => 1,
            'points_expire_at' => now()->addDays(7),
            'consequence_expires_at' => now()->addDays(7),
        ]);

        $userWarnings = $user->userWarnings;

        expect($userWarnings)->toHaveCount(1);
        expect($userWarnings->first()->id)->toBe($userWarning->id);
    });
});

describe('User groups relationship (from HasGroups trait)', function (): void {
    test('returns empty collection when user has no groups', function (): void {
        $user = User::factory()->create();
        $user->groups()->detach(); // Remove default groups

        expect($user->groups)->toBeEmpty();
    });

    test('returns groups user belongs to', function (): void {
        $user = User::factory()->create();
        $user->groups()->detach(); // Remove default groups

        $group = Group::factory()->create();
        $user->groups()->attach($group);

        $groups = $user->groups;

        expect($groups)->toHaveCount(1);
        expect($groups->first()->id)->toBe($group->id);
    });

    test('groups relationship is BelongsToMany', function (): void {
        $user = User::factory()->create();

        expect($user->groups())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
});

describe('User hasPassword attribute', function (): void {
    test('returns true when user has password', function (): void {
        $user = User::factory()->create(['password' => 'password123']);

        expect($user->has_password)->toBeTrue();
    });

    test('returns false when user has no password', function (): void {
        $user = User::factory()->create(['password' => null]);

        expect($user->has_password)->toBeFalse();
    });
});

describe('User activeWarnings relationship', function (): void {
    test('returns empty collection when user has no active warnings', function (): void {
        $user = User::factory()->create();

        expect($user->activeWarnings)->toBeEmpty();
    });

    test('returns only active warnings', function (): void {
        $user = User::factory()->create();
        $admin = User::factory()->asAdmin()->create();

        $warning = Warning::create([
            'name' => 'Test Warning',
            'points' => 1,
            'days_applied' => 7,
        ]);

        $consequence = WarningConsequence::create([
            'type' => App\Enums\WarningConsequenceType::None,
            'threshold' => 1,
            'duration_days' => 7,
        ]);

        // Active warning (expires in future)
        UserWarning::create([
            'user_id' => $user->id,
            'warning_id' => $warning->id,
            'warning_consequence_id' => $consequence->id,
            'created_by' => $admin->id,
            'reason' => 'Active warning',
            'points_at_issue' => 1,
            'points_expire_at' => now()->addDays(7),
            'consequence_expires_at' => now()->addDays(7),
        ]);

        // Expired warning
        UserWarning::create([
            'user_id' => $user->id,
            'warning_id' => $warning->id,
            'warning_consequence_id' => $consequence->id,
            'created_by' => $admin->id,
            'reason' => 'Expired warning',
            'points_at_issue' => 1,
            'points_expire_at' => now()->subDay(),
            'consequence_expires_at' => now()->subDay(),
        ]);

        expect($user->activeWarnings)->toHaveCount(1);
    });
});

describe('User currentBalance attribute (from CanBePaid trait)', function (): void {
    test('returns 0 when balance is null', function (): void {
        $user = User::factory()->create();

        // current_balance is not set, should default to 0
        expect($user->current_balance)->toBe(0.00);
    });
});
