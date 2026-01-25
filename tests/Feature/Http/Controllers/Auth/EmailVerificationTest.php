<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('verification notice page is displayed for unverified users', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertOk();
});

test('verification notice page redirects verified users to dashboard', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('email can be verified with valid signed url', function (): void {
    $user = User::factory()->unverified()->create();

    expect($user->hasVerifiedEmail())->toBeFalse();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1((string) $user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('verified event is dispatched when email is verified', function (): void {
    $user = User::factory()->unverified()->create();

    Event::fake([Verified::class]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1((string) $user->email)]
    );

    $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->id === $user->id);
});

test('email verification fails with invalid signature', function (): void {
    $user = User::factory()->unverified()->create();

    $verificationUrl = route('verification.verify', [
        'id' => $user->id,
        'hash' => sha1((string) $user->email),
    ]);

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email verification fails with invalid hash', function (): void {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email@example.com')]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email verification notification can be resent', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->post('/email/verification-notification');

    Notification::assertSentTo($user, VerifyEmail::class);
    $response->assertRedirect();
    $response->assertSessionHas('status', 'verification-link-sent');
});

test('email verification notification is not sent to already verified users', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/email/verification-notification');

    Notification::assertNotSentTo($user, VerifyEmail::class);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('guests cannot access verification notice page', function (): void {
    $response = $this->get('/verify-email');

    $response->assertRedirect('/login');
});

test('guests cannot verify email', function (): void {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1((string) $user->email)]
    );

    $response = $this->get($verificationUrl);

    $response->assertRedirect('/login');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('guests cannot request verification notification', function (): void {
    $response = $this->post('/email/verification-notification');

    $response->assertRedirect('/login');
});

test('already verified users are redirected with verified query param', function (): void {
    $user = User::factory()->create();

    Event::fake([Verified::class]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1((string) $user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email verification is throttled', function (): void {
    $user = User::factory()->unverified()->create();

    for ($i = 0; $i < 6; $i++) {
        $this->actingAs($user)->post('/email/verification-notification');
    }

    $response = $this->actingAs($user)->post('/email/verification-notification');

    $response->assertStatus(429);
});
