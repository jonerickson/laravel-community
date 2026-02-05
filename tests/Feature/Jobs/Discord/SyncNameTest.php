<?php

declare(strict_types=1);

use App\Enums\DiscordNameSyncDirection;
use App\Jobs\Discord\SyncName;
use App\Models\User;
use App\Models\UserIntegration;
use App\Services\Integrations\DiscordService;
use App\Settings\IntegrationSettings;

beforeEach(function (): void {
    $this->settings = app(IntegrationSettings::class);
});

test('job does nothing when name sync is disabled', function (): void {
    $this->settings->discord_name_sync_enabled = false;
    $this->settings->save();

    $user = User::factory()->create();
    UserIntegration::factory()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    $discordService = Mockery::mock(DiscordService::class);
    $discordService->shouldNotReceive('isUserInServer');
    $discordService->shouldNotReceive('getMemberNickname');
    $discordService->shouldNotReceive('setMemberNickname');

    app()->instance(DiscordService::class, $discordService);

    new SyncName($user->id)->handle($this->settings);
});

test('job does nothing when sync direction is not set', function (): void {
    $this->settings->discord_name_sync_enabled = true;
    $this->settings->discord_name_sync_direction = null;
    $this->settings->save();

    $user = User::factory()->create();
    UserIntegration::factory()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    $discordService = Mockery::mock(DiscordService::class);
    $discordService->shouldNotReceive('isUserInServer');

    app()->instance(DiscordService::class, $discordService);

    new SyncName($user->id)->handle($this->settings);
});

test('job does nothing when user has no discord integration', function (): void {
    $this->settings->discord_name_sync_enabled = true;
    $this->settings->discord_name_sync_direction = DiscordNameSyncDirection::DiscordToApp;
    $this->settings->save();

    $user = User::factory()->create();

    $discordService = Mockery::mock(DiscordService::class);
    $discordService->shouldNotReceive('isUserInServer');

    app()->instance(DiscordService::class, $discordService);

    new SyncName($user->id)->handle($this->settings);
});

test('job does nothing when user is not in discord server', function (): void {
    $this->settings->discord_name_sync_enabled = true;
    $this->settings->discord_name_sync_direction = DiscordNameSyncDirection::DiscordToApp;
    $this->settings->save();

    $user = User::factory()->create();
    UserIntegration::factory()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    $discordService = Mockery::mock(DiscordService::class);
    $discordService->shouldReceive('isUserInServer')->with('123456789')->andReturn(false);
    $discordService->shouldNotReceive('getMemberNickname');

    app()->instance(DiscordService::class, $discordService);

    new SyncName($user->id)->handle($this->settings);
});

test('job syncs discord name to app when direction is discord to app', function (): void {
    $this->settings->discord_name_sync_enabled = true;
    $this->settings->discord_name_sync_direction = DiscordNameSyncDirection::DiscordToApp;
    $this->settings->save();

    $user = User::factory()->create(['name' => 'Original Name']);
    UserIntegration::factory()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    $discordService = Mockery::mock(DiscordService::class);
    $discordService->shouldReceive('isUserInServer')->with('123456789')->andReturn(true);
    $discordService->shouldReceive('getMemberNickname')->with('123456789')->andReturn('Discord Name');

    app()->instance(DiscordService::class, $discordService);

    new SyncName($user->id)->handle($this->settings);

    expect($user->fresh()->name)->toBe('Discord Name');
});

test('job does not update app name when discord name matches', function (): void {
    $this->settings->discord_name_sync_enabled = true;
    $this->settings->discord_name_sync_direction = DiscordNameSyncDirection::DiscordToApp;
    $this->settings->save();

    $user = User::factory()->create(['name' => 'Same Name']);
    UserIntegration::factory()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    $discordService = Mockery::mock(DiscordService::class);
    $discordService->shouldReceive('isUserInServer')->with('123456789')->andReturn(true);
    $discordService->shouldReceive('getMemberNickname')->with('123456789')->andReturn('Same Name');

    app()->instance(DiscordService::class, $discordService);

    new SyncName($user->id)->handle($this->settings);

    expect($user->fresh()->name)->toBe('Same Name');
});

test('job syncs app name to discord when direction is app to discord', function (): void {
    $this->settings->discord_name_sync_enabled = true;
    $this->settings->discord_name_sync_direction = DiscordNameSyncDirection::AppToDiscord;
    $this->settings->save();

    $user = User::factory()->create(['name' => 'App Name']);
    UserIntegration::factory()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    $discordService = Mockery::mock(DiscordService::class);
    $discordService->shouldReceive('isUserInServer')->with('123456789')->andReturn(true);
    $discordService->shouldReceive('getMemberNickname')->with('123456789')->andReturn('Discord Name');
    $discordService->shouldReceive('setMemberNickname')->with('123456789', 'App Name')->once()->andReturn(true);

    app()->instance(DiscordService::class, $discordService);

    new SyncName($user->id)->handle($this->settings);
});

test('job does not update discord name when names match', function (): void {
    $this->settings->discord_name_sync_enabled = true;
    $this->settings->discord_name_sync_direction = DiscordNameSyncDirection::AppToDiscord;
    $this->settings->save();

    $user = User::factory()->create(['name' => 'Same Name']);
    UserIntegration::factory()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    $discordService = Mockery::mock(DiscordService::class);
    $discordService->shouldReceive('isUserInServer')->with('123456789')->andReturn(true);
    $discordService->shouldReceive('getMemberNickname')->with('123456789')->andReturn('Same Name');
    $discordService->shouldNotReceive('setMemberNickname');

    app()->instance(DiscordService::class, $discordService);

    new SyncName($user->id)->handle($this->settings);
});

test('job uses provider name as fallback when no discord nickname', function (): void {
    $this->settings->discord_name_sync_enabled = true;
    $this->settings->discord_name_sync_direction = DiscordNameSyncDirection::DiscordToApp;
    $this->settings->save();

    $user = User::factory()->create(['name' => 'Original Name']);
    UserIntegration::factory()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
        'provider_name' => 'Fallback Name',
    ]);

    $discordService = Mockery::mock(DiscordService::class);
    $discordService->shouldReceive('isUserInServer')->with('123456789')->andReturn(true);
    $discordService->shouldReceive('getMemberNickname')->with('123456789')->andReturn(null);

    app()->instance(DiscordService::class, $discordService);

    new SyncName($user->id)->handle($this->settings);

    expect($user->fresh()->name)->toBe('Fallback Name');
});
