<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Event;

test('registration page redirects to onboarding', function (): void {
    $response = $this->get('/register');

    $response->assertRedirect('/onboarding');
});

test('users can register with valid credentials', function (): void {
    $response = $this->post('/register', [
        'name' => 'TestUser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertDatabaseHas('users', [
        'name' => 'TestUser',
        'email' => 'test@example.com',
    ]);
});

test('registered event is dispatched on registration', function (): void {
    Event::fake([Illuminate\Auth\Events\Registered::class]);

    $this->post('/register', [
        'name' => 'TestUser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    Event::assertDispatched(Illuminate\Auth\Events\Registered::class, fn ($event): bool => $event->user->email === 'test@example.com');
});

test('registration fails with missing name', function (): void {
    $response = $this->post('/register', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['name']);
    $this->assertGuest();
});

test('registration fails with name too short', function (): void {
    $response = $this->post('/register', [
        'name' => 'A',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['name']);
    $this->assertGuest();
});

test('registration fails with name too long', function (): void {
    $response = $this->post('/register', [
        'name' => str_repeat('a', 33),
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['name']);
    $this->assertGuest();
});

test('registration fails with missing email', function (): void {
    $response = $this->post('/register', [
        'name' => 'TestUser',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('registration fails with invalid email format', function (): void {
    $response = $this->post('/register', [
        'name' => 'TestUser',
        'email' => 'not-an-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('registration fails with duplicate email', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->post('/register', [
        'name' => 'TestUser',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('registration fails with missing password', function (): void {
    $response = $this->post('/register', [
        'name' => 'TestUser',
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('registration fails when password confirmation does not match', function (): void {
    $response = $this->post('/register', [
        'name' => 'TestUser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('registration fails with uppercase email', function (): void {
    $response = $this->post('/register', [
        'name' => 'TestUser',
        'email' => 'Test@EXAMPLE.COM',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('password is hashed', function (): void {
    $this->post('/register', [
        'name' => 'TestUser',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect($user->password)->not->toBe('password123');
    expect(password_verify('password123', (string) $user->password))->toBeTrue();
});

test('registration is throttled after too many attempts', function (): void {
    for ($i = 0; $i < 10; $i++) {
        $this->post('/register', [
            'name' => 'TestUser',
            'email' => sprintf('test%d@example.com', $i),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
    }

    $response = $this->post('/register', [
        'name' => 'TestUser',
        'email' => 'throttled@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(429);
});
