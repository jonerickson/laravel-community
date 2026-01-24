<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('forgot password page is displayed', function (): void {
    $response = $this->get('/forgot-password');

    $response->assertOk();
});

test('password reset link can be requested', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
    $response->assertRedirect();
    $response->assertSessionHas('status', 'A reset link will be sent if the account exists.');
});

test('password reset link request returns same message for non-existent email', function (): void {
    Notification::fake();

    $response = $this->post('/forgot-password', ['email' => 'nonexistent@example.com']);

    Notification::assertNothingSent();
    $response->assertRedirect();
    $response->assertSessionHas('status', 'A reset link will be sent if the account exists.');
});

test('password reset link request requires email', function (): void {
    $response = $this->post('/forgot-password', []);

    $response->assertSessionHasErrors(['email']);
});

test('password reset link request requires valid email', function (): void {
    $response = $this->post('/forgot-password', ['email' => 'not-an-email']);

    $response->assertSessionHasErrors(['email']);
});

test('reset password page is displayed with valid token', function (): void {
    $user = User::factory()->create();

    $token = Password::createToken($user);

    $response = $this->get('/reset-password/'.$token.'?email='.$user->email);

    $response->assertOk();
});

test('password can be reset with valid token', function (): void {
    $user = User::factory()->create();

    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect('/login');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('password reset event is dispatched on successful reset', function (): void {
    $user = User::factory()->create();

    Event::fake([PasswordReset::class]);

    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->id === $user->id);
});

test('password reset fails with invalid token', function (): void {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $response = $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors(['email']);

    expect($user->fresh()->password)->toBe($originalPassword);
});

test('password reset fails with wrong email', function (): void {
    $user = User::factory()->create();

    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'wrong@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('password reset requires token', function (): void {
    $user = User::factory()->create();

    $response = $this->post('/reset-password', [
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors(['token']);
});

test('password reset requires email', function (): void {
    $user = User::factory()->create();

    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('password reset requires password confirmation', function (): void {
    $user = User::factory()->create();

    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
    ]);

    $response->assertSessionHasErrors(['password']);
});

test('password reset fails when passwords do not match', function (): void {
    $user = User::factory()->create();

    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors(['password']);
});

test('password reset is throttled', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 6; $i++) {
        $this->post('/forgot-password', ['email' => $user->email]);
    }

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response->assertStatus(429);
});

test('authenticated users cannot access forgot password page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/forgot-password');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('authenticated users cannot access reset password page', function (): void {
    $user = User::factory()->create();

    $token = Password::createToken($user);

    $response = $this->actingAs($user)->get('/reset-password/'.$token);

    $response->assertRedirect(route('dashboard', absolute: false));
});
