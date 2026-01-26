<?php

declare(strict_types=1);

use App\Models\User;

test('login page is displayed', function (): void {
    $response = $this->get('/login');

    $response->assertOk();
});

test('users can authenticate with valid credentials', function (): void {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can authenticate with remember me option', function (): void {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users cannot authenticate with invalid password', function (): void {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users cannot authenticate with non-existent email', function (): void {
    $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('login fails with validation errors for missing email', function (): void {
    $response = $this->post('/login', [
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('login fails with validation errors for missing password', function (): void {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('login fails with validation errors for invalid email format', function (): void {
    $response = $this->post('/login', [
        'email' => 'not-an-email',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('users can logout', function (): void {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();

    $response = $this->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('users are throttled after too many failed login attempts', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
    $this->assertGuest();
});

test('rate limiter is cleared after successful login', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();

    $this->post('/logout');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('login page stores intended redirect url', function (): void {
    $response = $this->get('/login?redirect='.urlencode('/settings/profile'));

    $response->assertOk();
});

test('users without password must reset password or use social login', function (): void {
    $user = User::factory()->create([
        'password' => null,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'any-password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});
