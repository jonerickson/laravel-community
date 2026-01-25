<?php

declare(strict_types=1);

use App\Models\User;

test('downloads settings page is displayed for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/downloads');

    $response->assertOk();
});

test('downloads settings page redirects guests to login', function (): void {
    $response = $this->get('/settings/downloads');

    $response->assertRedirect('/login');
});

test('downloads settings page returns Inertia response with downloads', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/downloads');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/downloads')
        ->has('downloads'));
});

test('downloads settings page shows empty downloads for user without orders', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/downloads');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/downloads')
        ->has('downloads', 0));
});
