<?php

declare(strict_types=1);

use App\Models\User;

test('appearance settings page is displayed for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/appearance');

    $response->assertOk();
});

test('appearance settings page redirects guests to login', function (): void {
    $response = $this->get('/settings/appearance');

    $response->assertRedirect('/login');
});

test('appearance settings page returns Inertia response', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/appearance');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('settings/appearance'));
});
