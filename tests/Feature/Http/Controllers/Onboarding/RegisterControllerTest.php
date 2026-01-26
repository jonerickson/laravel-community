<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

test('guest can register via onboarding', function (): void {
    $response = $this->post(route('onboarding.register'), [
        'name' => 'TestUser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('onboarding'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'name' => 'TestUser',
        'email' => 'test@example.com',
    ]);
});

test('registered event is dispatched on onboarding registration', function (): void {
    Event::fake([Registered::class]);

    $this->post(route('onboarding.register'), [
        'name' => 'TestUser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    Event::assertDispatched(Registered::class, fn ($event): bool => $event->user->email === 'test@example.com');
});

test('onboarding registration fails with missing name', function (): void {
    $response = $this->post(route('onboarding.register'), [
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['name']);
    $this->assertGuest();
});

test('onboarding registration fails with name too short', function (): void {
    $response = $this->post(route('onboarding.register'), [
        'name' => 'A',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['name']);
    $this->assertGuest();
});

test('onboarding registration fails with name too long', function (): void {
    $response = $this->post(route('onboarding.register'), [
        'name' => str_repeat('a', 33),
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['name']);
    $this->assertGuest();
});

test('onboarding registration fails with missing email', function (): void {
    $response = $this->post(route('onboarding.register'), [
        'name' => 'TestUser',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('onboarding registration fails with invalid email format', function (): void {
    $response = $this->post(route('onboarding.register'), [
        'name' => 'TestUser',
        'email' => 'not-an-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('onboarding registration fails with duplicate email', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->post(route('onboarding.register'), [
        'name' => 'TestUser',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('onboarding registration fails with missing password', function (): void {
    $response = $this->post(route('onboarding.register'), [
        'name' => 'TestUser',
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('onboarding registration fails when password confirmation does not match', function (): void {
    $response = $this->post(route('onboarding.register'), [
        'name' => 'TestUser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('onboarding registration is throttled after too many attempts', function (): void {
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('onboarding.register'), [
            'name' => 'TestUser',
            'email' => sprintf('test%d@example.com', $i),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
    }

    $response = $this->post(route('onboarding.register'), [
        'name' => 'TestUser',
        'email' => 'throttled@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(429);
});
