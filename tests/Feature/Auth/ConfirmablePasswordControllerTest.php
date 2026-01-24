<?php

declare(strict_types=1);

use App\Models\User;

test('password confirmation page is displayed', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/confirm-password');

    $response->assertOk();
});

test('guests cannot access password confirmation page', function (): void {
    $response = $this->get('/confirm-password');

    $response->assertRedirect(route('login'));
});

test('correct password confirms successfully', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $response->assertSessionHasNoErrors();
});

test('password confirmation sets session timestamp', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    expect(session('auth.password_confirmed_at'))->toBeGreaterThan(0);
});

test('incorrect password fails confirmation', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors(['password']);
});

test('password confirmation fails with empty password', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => '',
    ]);

    $response->assertSessionHasErrors(['password']);
});

test('password confirmation redirects to intended url', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    // Store an intended URL in the session
    $intendedUrl = '/settings/billing';

    $response = $this->actingAs($user)
        ->withSession(['url.intended' => $intendedUrl])
        ->post('/confirm-password', [
            'password' => 'password',
        ]);

    $response->assertRedirect($intendedUrl);
});

test('guests cannot confirm password', function (): void {
    $response = $this->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('login'));
});
