<?php

declare(strict_types=1);

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

test('redirect to discord provider returns redirect response', function (): void {
    $mockProvider = Mockery::mock(AbstractProvider::class, function (MockInterface $mock): void {
        $mock->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://discord.com/oauth2/authorize'));
    });

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturn($mockProvider);

    $response = $this->get('/oauth/redirect/discord');

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain('discord.com');
});

test('redirect to roblox provider returns redirect response', function (): void {
    $mockProvider = Mockery::mock(AbstractProvider::class, function (MockInterface $mock): void {
        $mock->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://apis.roblox.com/oauth/v1/authorize'));
    });

    Socialite::shouldReceive('driver')
        ->with('roblox')
        ->once()
        ->andReturn($mockProvider);

    $response = $this->get('/oauth/redirect/roblox');

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain('roblox.com');
});

test('redirect with invalid provider throws exception', function (): void {
    Socialite::shouldReceive('driver')
        ->with('invalid-provider')
        ->once()
        ->andThrow(new InvalidArgumentException('Driver [invalid-provider] not supported.'));

    $response = $this->get('/oauth/redirect/invalid-provider');

    $response->assertStatus(500);
});

test('redirect stores intended url when redirect query parameter is provided', function (): void {
    $mockProvider = Mockery::mock(AbstractProvider::class, function (MockInterface $mock): void {
        $mock->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://discord.com/oauth2/authorize'));
    });

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturn($mockProvider);

    $intendedUrl = 'https://example.com/dashboard';
    $response = $this->get('/oauth/redirect/discord?redirect='.urlencode($intendedUrl));

    $response->assertRedirect();

    expect(session('url.intended'))->toBe($intendedUrl);
});

test('redirect does not store intended url when redirect query parameter is empty', function (): void {
    $mockProvider = Mockery::mock(AbstractProvider::class, function (MockInterface $mock): void {
        $mock->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://discord.com/oauth2/authorize'));
    });

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturn($mockProvider);

    $response = $this->get('/oauth/redirect/discord?redirect=');

    $response->assertRedirect();

    expect(session('url.intended'))->toBeNull();
});

test('redirect does not store intended url when no redirect query parameter', function (): void {
    $mockProvider = Mockery::mock(AbstractProvider::class, function (MockInterface $mock): void {
        $mock->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://discord.com/oauth2/authorize'));
    });

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturn($mockProvider);

    $response = $this->get('/oauth/redirect/discord');

    $response->assertRedirect();

    expect(session('url.intended'))->toBeNull();
});

test('redirect works for authenticated users', function (): void {
    $user = App\Models\User::factory()->create();

    $mockProvider = Mockery::mock(AbstractProvider::class, function (MockInterface $mock): void {
        $mock->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://discord.com/oauth2/authorize'));
    });

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturn($mockProvider);

    $response = $this->actingAs($user)->get('/oauth/redirect/discord');

    $response->assertRedirect();
});

test('redirect works for guests', function (): void {
    $mockProvider = Mockery::mock(AbstractProvider::class, function (MockInterface $mock): void {
        $mock->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://discord.com/oauth2/authorize'));
    });

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturn($mockProvider);

    $response = $this->get('/oauth/redirect/discord');

    $response->assertRedirect();
    $this->assertGuest();
});

test('redirect decodes url-encoded redirect parameter', function (): void {
    $mockProvider = Mockery::mock(AbstractProvider::class, function (MockInterface $mock): void {
        $mock->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://discord.com/oauth2/authorize'));
    });

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->once()
        ->andReturn($mockProvider);

    $intendedUrl = 'https://example.com/path?query=value&other=test';
    $encodedUrl = urlencode($intendedUrl);

    $response = $this->get('/oauth/redirect/discord?redirect='.$encodedUrl);

    $response->assertRedirect();

    expect(session('url.intended'))->toBe($intendedUrl);
});
