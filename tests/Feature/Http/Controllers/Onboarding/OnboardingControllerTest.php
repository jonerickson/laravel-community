<?php

declare(strict_types=1);

use App\Enums\FieldType;
use App\Managers\PaymentManager;
use App\Models\Field;
use App\Models\Policy;
use App\Models\PolicyCategory;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\UserIntegration;
use App\Settings\RegistrationSettings;
use Inertia\Testing\AssertableInertia;

test('guest can view onboarding page', function (): void {
    $response = $this->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('isAuthenticated', false)
        ->where('initialStep', 0)
    );
});

test('authenticated user can view onboarding page', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('isAuthenticated', true)
    );
});

test('already onboarded user is redirected to home', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('message', 'Your account has already been successfully onboarded.');
});

test('onboarding page displays custom fields', function (): void {
    $field = Field::factory()->create([
        'name' => 'test_field',
        'label' => 'Test Field',
        'type' => FieldType::Number,
        'is_required' => false,
    ]);

    $response = $this->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->has('customFields', 1)
    );
});

test('onboarding page displays subscription products', function (): void {
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()
        ->subscription()
        ->approved()
        ->active()
        ->visible()
        ->create();
    $product->categories()->attach($category);
    Price::factory()
        ->recurring()
        ->active()
        ->create([
            'product_id' => $product->id,
            'is_visible' => true,
        ]);

    $response = $this->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->has('subscriptions', 1)
    );
});

test('onboarding page filters out inactive subscription products', function (): void {
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()
        ->subscription()
        ->approved()
        ->inactive()
        ->visible()
        ->create();
    $product->categories()->attach($category);
    Price::factory()->recurring()->active()->create([
        'product_id' => $product->id,
        'is_visible' => true,
    ]);

    $response = $this->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->has('subscriptions', 0)
    );
});

test('onboarding page displays required policies', function (): void {
    $category = PolicyCategory::factory()->create(['is_active' => true]);
    $policy = Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => true,
    ]);

    $settings = app(RegistrationSettings::class);
    $settings->required_policy_ids = [$policy->id];
    $settings->save();

    $response = $this->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->has('policies', 1)
    );
});

test('initial step is 0 for guest', function (): void {
    $response = $this->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('initialStep', 0)
    );
});

test('initial step is 1 for authenticated unverified user', function (): void {
    $user = User::factory()->unverified()->notOnboarded()->create();

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('initialStep', 1)
    );
});

test('initial step is 2 for authenticated verified user without integrations', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('initialStep', 2)
    );
});

test('initial step is 3 for authenticated verified user with integrations', function (): void {
    $user = User::factory()->notOnboarded()->create();
    UserIntegration::create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('initialStep', 3)
    );
});

test('onboarding page shows discord integration status', function (): void {
    config(['services.discord.enabled' => true]);
    $user = User::factory()->notOnboarded()->create();
    UserIntegration::create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->has('integrations.discord.connected')
    );
});

test('onboarding page shows roblox integration status', function (): void {
    config(['services.roblox.enabled' => true]);
    $user = User::factory()->notOnboarded()->create();
    UserIntegration::create([
        'user_id' => $user->id,
        'provider' => 'roblox',
        'provider_id' => '123456789',
    ]);

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->has('integrations.roblox.connected')
    );
});

test('onboarding page shows email verified status', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('emailVerified', true)
    );
});

test('onboarding page shows email not verified status', function (): void {
    $user = User::factory()->unverified()->notOnboarded()->create();

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('emailVerified', false)
    );
});

test('onboarding page shows subscription status', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $subscriptionData = App\Data\SubscriptionData::from([
        'name' => 'default',
        'doesNotExpire' => false,
    ]);

    $paymentManagerMock = $this->mock(PaymentManager::class);
    $paymentManagerMock
        ->shouldReceive('currentSubscription')
        ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
        ->andReturn($subscriptionData);

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('hasSubscription', true)
    );
});

test('onboarding page shows no subscription status', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $paymentManagerMock = $this->mock(PaymentManager::class);
    $paymentManagerMock
        ->shouldReceive('currentSubscription')
        ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
        ->andReturnNull();

    $response = $this->actingAs($user)->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('onboarding/index')
        ->where('hasSubscription', false)
    );
});

// Store (complete onboarding)
test('guest cannot complete onboarding', function (): void {
    $response = $this->post(route('onboarding.store'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can complete onboarding', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->post(route('onboarding.store'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('message', 'Your onboarding has been successfully completed.');

    expect($user->fresh()->onboarded_at)->not->toBeNull();
});

test('complete onboarding respects intended URL', function (): void {
    $user = User::factory()->notOnboarded()->create();
    session(['url.intended' => route('settings.profile.edit')]);

    $response = $this->actingAs($user)->post(route('onboarding.store'));

    $response->assertRedirect(route('settings.profile.edit'));
});

// Update (step navigation)
test('guest cannot update onboarding step', function (): void {
    $response = $this->put(route('onboarding.update'), [
        'step' => 2,
    ]);

    $response->assertRedirect(route('login'));
});

test('authenticated user can update onboarding step', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->put(route('onboarding.update'), [
        'step' => 3,
    ]);

    $response->assertRedirect(route('onboarding'));
});

test('update step requires step parameter', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->put(route('onboarding.update'), []);

    $response->assertSessionHasErrors(['step']);
});

test('update step requires numeric step', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->put(route('onboarding.update'), [
        'step' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['step']);
});
