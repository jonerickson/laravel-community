<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserIntegration;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function createMockSocialiteUser(
    string $id = '123456789',
    string $name = 'Test User',
    string $email = 'test@example.com',
    string $avatar = 'https://example.com/avatar.png',
): SocialiteUser {
    $user = new SocialiteUser;
    $user->id = $id;
    $user->name = $name;
    $user->email = $email;
    $user->avatar = $avatar;
    $user->token = 'mock-access-token';
    $user->refreshToken = 'mock-refresh-token';
    $user->expiresIn = 3600;

    return $user;
}

test('guest callback with provider error redirects to login with error message', function (): void {
    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andThrow(new Exception('OAuth provider error'));

    $response = $this->get('/oauth/callback/discord');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'There was an error while trying to login. Please try again.');
});

test('guest callback with no existing integration redirects to login with no account message', function (): void {
    $socialiteUser = createMockSocialiteUser();

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $response = $this->get('/oauth/callback/discord');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'No account found for this Discord connection. Please create an account first or login using your username and password.');
});

test('guest callback with existing integration logs in user and redirects to dashboard', function (): void {
    $user = User::factory()->create();
    $integration = UserIntegration::create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
        'provider_name' => 'Old Name',
        'provider_email' => 'old@example.com',
        'provider_avatar' => 'https://old.example.com/avatar.png',
    ]);

    $socialiteUser = createMockSocialiteUser();

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $response = $this->get('/oauth/callback/discord');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('message', 'You have been successfully logged in.');

    $this->assertAuthenticatedAs($user);

    $integration->refresh();
    expect($integration->provider_name)->toBe('Test User');
    expect($integration->provider_avatar)->toBe('https://example.com/avatar.png');
});

test('authenticated user callback with provider error redirects to integrations with error message', function (): void {
    $user = User::factory()->create();

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andThrow(new Exception('OAuth provider error'));

    $response = $this->actingAs($user)->get('/oauth/callback/discord');

    $response->assertRedirect(route('settings.integrations.index'));
    $response->assertSessionHas('message', 'There was an error while trying to connect your again. Please try again.');
    $response->assertSessionHas('messageVariant', 'error');
});

test('authenticated user callback with no existing integration creates new integration', function (): void {
    $user = User::factory()->create();

    $socialiteUser = createMockSocialiteUser(
        id: '987654321',
        name: 'Discord User',
        email: 'discord@example.com',
        avatar: 'https://discord.com/avatar.png',
    );

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $response = $this->actingAs($user)->get('/oauth/callback/discord');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('message', 'You have been successfully connected your account.');

    $this->assertDatabaseHas('users_integrations', [
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '987654321',
        'provider_name' => 'Discord User',
        'provider_email' => 'discord@example.com',
        'provider_avatar' => 'https://discord.com/avatar.png',
    ]);
});

test('authenticated user callback with existing integration updates integration', function (): void {
    $user = User::factory()->create();
    $integration = UserIntegration::create([
        'user_id' => $user->id,
        'provider' => 'roblox',
        'provider_id' => '555666777',
        'provider_name' => 'Old Roblox Name',
        'provider_email' => null,
        'provider_avatar' => 'https://old.roblox.com/avatar.png',
    ]);

    $socialiteUser = createMockSocialiteUser(
        id: '555666777',
        name: 'New Roblox Name',
        email: 'roblox@example.com',
        avatar: 'https://new.roblox.com/avatar.png',
    );

    Socialite::shouldReceive('driver')
        ->with('roblox')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $response = $this->actingAs($user)->get('/oauth/callback/roblox');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('message', 'You have been successfully connected your account.');

    $integration->refresh();
    expect($integration->provider_name)->toBe('New Roblox Name');
    expect($integration->provider_avatar)->toBe('https://new.roblox.com/avatar.png');
});

test('guest callback respects intended url from session', function (): void {
    $user = User::factory()->create();
    UserIntegration::create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
        'provider_name' => 'Test User',
    ]);

    $socialiteUser = createMockSocialiteUser();

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $intendedUrl = route('settings.profile.edit');
    $response = $this->withSession(['url.intended' => $intendedUrl])->get('/oauth/callback/discord');

    $response->assertRedirect($intendedUrl);
});

test('authenticated user callback respects intended url from session', function (): void {
    $user = User::factory()->create();

    $socialiteUser = createMockSocialiteUser(id: 'new-provider-id');

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $intendedUrl = route('settings.profile.edit');
    $response = $this->actingAs($user)
        ->withSession(['url.intended' => $intendedUrl])
        ->get('/oauth/callback/discord');

    $response->assertRedirect($intendedUrl);
});

test('callback works with discord provider', function (): void {
    $user = User::factory()->create();

    $socialiteUser = createMockSocialiteUser(id: 'discord-user-id');

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $response = $this->actingAs($user)->get('/oauth/callback/discord');

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users_integrations', [
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => 'discord-user-id',
    ]);
});

test('callback works with roblox provider', function (): void {
    $user = User::factory()->create();

    $socialiteUser = createMockSocialiteUser(id: 'roblox-user-id');

    Socialite::shouldReceive('driver')
        ->with('roblox')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $response = $this->actingAs($user)->get('/oauth/callback/roblox');

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users_integrations', [
        'user_id' => $user->id,
        'provider' => 'roblox',
        'provider_id' => 'roblox-user-id',
    ]);
});

test('callback with invalid provider redirects with error', function (): void {
    Socialite::shouldReceive('driver')
        ->with('invalid-provider')
        ->once()
        ->andThrow(new InvalidArgumentException('Driver [invalid-provider] not supported.'));

    $response = $this->get('/oauth/callback/invalid-provider');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
});

test('new integration stores access and refresh tokens', function (): void {
    $user = User::factory()->create();

    $socialiteUser = createMockSocialiteUser(id: 'new-user-id');

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $this->actingAs($user)->get('/oauth/callback/discord');

    $integration = UserIntegration::where('user_id', $user->id)
        ->where('provider', 'discord')
        ->first();

    expect($integration)->not->toBeNull();
    expect($integration->access_token)->toBe('mock-access-token');
    expect($integration->refresh_token)->toBe('mock-refresh-token');
    expect($integration->expires_at)->not->toBeNull();
});

test('integration sets last synced at timestamp on creation', function (): void {
    $user = User::factory()->create();

    $socialiteUser = createMockSocialiteUser(id: 'sync-test-user');

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturnSelf();

    Socialite::shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    $this->freezeTime();
    $this->actingAs($user)->get('/oauth/callback/discord');

    $integration = UserIntegration::where('user_id', $user->id)
        ->where('provider', 'discord')
        ->first();

    expect($integration->last_synced_at->toDateTimeString())->toBe(now()->toDateTimeString());
});
