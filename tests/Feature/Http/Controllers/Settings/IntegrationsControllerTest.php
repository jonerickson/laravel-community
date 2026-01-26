<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserIntegration;

test('integrations settings page is displayed for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/integrations');

    $response->assertOk();
});

test('integrations settings page redirects guests to login', function (): void {
    $response = $this->get('/settings/integrations');

    $response->assertRedirect('/login');
});

test('integrations settings page returns Inertia response with connected accounts', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/integrations');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/integrations')
        ->has('connectedAccounts'));
});

test('integrations settings page shows user integrations', function (): void {
    $user = User::factory()->create();

    UserIntegration::query()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
        'provider_name' => 'TestUser',
        'provider_email' => 'test@example.com',
    ]);

    $response = $this->actingAs($user)->get('/settings/integrations');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/integrations')
        ->has('connectedAccounts', 1));
});

test('integration can be deleted by owner', function (): void {
    $user = User::factory()->create();

    $integration = UserIntegration::query()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
        'provider_name' => 'TestUser',
    ]);

    $response = $this->actingAs($user)->delete('/settings/integrations/'.$integration->id);

    $response->assertRedirect();
    $response->assertSessionHas('message');

    expect(UserIntegration::find($integration->id))->toBeNull();
});

test('integration cannot be deleted by another user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $integration = UserIntegration::query()->create([
        'user_id' => $otherUser->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
        'provider_name' => 'TestUser',
    ]);

    $response = $this->actingAs($user)->delete('/settings/integrations/'.$integration->id);

    $response->assertForbidden();

    expect(UserIntegration::find($integration->id))->not->toBeNull();
});

test('integration deletion redirects guests to login', function (): void {
    $user = User::factory()->create();

    $integration = UserIntegration::query()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
        'provider_name' => 'TestUser',
    ]);

    $response = $this->delete('/settings/integrations/'.$integration->id);

    $response->assertRedirect('/login');

    expect(UserIntegration::find($integration->id))->not->toBeNull();
});

test('integrations page shows multiple providers', function (): void {
    $user = User::factory()->create();

    UserIntegration::query()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
        'provider_name' => 'DiscordUser',
    ]);

    UserIntegration::query()->create([
        'user_id' => $user->id,
        'provider' => 'roblox',
        'provider_id' => '987654321',
        'provider_name' => 'RobloxUser',
    ]);

    $response = $this->actingAs($user)->get('/settings/integrations');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/integrations')
        ->has('connectedAccounts', 2));
});

test('integrations page does not show other users integrations', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserIntegration::query()->create([
        'user_id' => $otherUser->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
        'provider_name' => 'OtherUser',
    ]);

    $response = $this->actingAs($user)->get('/settings/integrations');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/integrations')
        ->has('connectedAccounts', 0));
});
