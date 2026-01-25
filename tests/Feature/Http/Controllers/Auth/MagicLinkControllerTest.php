<?php

declare(strict_types=1);

use App\Mail\Auth\MagicLinkMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

test('magic link request page is displayed', function (): void {
    $response = $this->get('/magic-link');

    $response->assertOk();
});

test('magic link can be requested', function (): void {
    Mail::fake();

    $user = User::factory()->create();

    $response = $this->post('/magic-link', ['email' => $user->email]);

    Mail::assertQueued(MagicLinkMail::class, fn (MagicLinkMail $mail): bool => $mail->hasTo($user->email) && $mail->user->id === $user->id);
    $response->assertRedirect();
    $response->assertSessionHas('status', 'If an account exists with that email address, a magic link has been sent.');
});

test('magic link request returns same message for non-existent email', function (): void {
    Mail::fake();

    $response = $this->post('/magic-link', ['email' => 'nonexistent@example.com']);

    Mail::assertNothingQueued();
    $response->assertRedirect();
    $response->assertSessionHas('status', 'If an account exists with that email address, a magic link has been sent.');
});

test('magic link request requires email', function (): void {
    $response = $this->post('/magic-link', []);

    $response->assertSessionHasErrors(['email']);
});

test('magic link request requires valid email', function (): void {
    $response = $this->post('/magic-link', ['email' => 'not-an-email']);

    $response->assertSessionHasErrors(['email']);
});

test('magic link login works with valid signature', function (): void {
    $user = User::factory()->create();

    $url = URL::temporarySignedRoute('magic-link.login', now()->addMinutes(15), [
        'user' => $user->reference_id,
    ]);

    $response = $this->get($url);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('magic link login fails with invalid signature', function (): void {
    $user = User::factory()->create();

    $response = $this->get('/magic-link/login/'.$user->reference_id.'?signature=invalid');

    $response->assertStatus(403);
});

test('magic link login fails with expired signature', function (): void {
    $user = User::factory()->create();

    $url = URL::temporarySignedRoute('magic-link.login', now()->subMinutes(1), [
        'user' => $user->reference_id,
    ]);

    $response = $this->get($url);

    $response->assertStatus(403);
});

test('magic link login fails with tampered url', function (): void {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $url = URL::temporarySignedRoute('magic-link.login', now()->addMinutes(15), [
        'user' => $user->reference_id,
    ]);

    // Tamper with the URL by changing to another user's reference ID (signature won't match)
    $tamperedUrl = str_replace($user->reference_id, $anotherUser->reference_id, $url);

    $response = $this->get($tamperedUrl);

    // Signature validation fails because URL was signed for a different user
    $response->assertStatus(403);
});

test('magic link login regenerates session', function (): void {
    $user = User::factory()->create();

    $url = URL::temporarySignedRoute('magic-link.login', now()->addMinutes(15), [
        'user' => $user->reference_id,
    ]);

    $oldSessionId = session()->getId();

    $this->get($url);

    expect(session()->getId())->not->toBe($oldSessionId);
});

test('magic link request is throttled', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/magic-link', ['email' => $user->email]);
    }

    $response = $this->post('/magic-link', ['email' => $user->email]);

    $response->assertStatus(429);
});

test('magic link login is throttled', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->get('/magic-link/login/'.$user->reference_id.'?signature=invalid');
    }

    $response = $this->get('/magic-link/login/'.$user->reference_id.'?signature=invalid');

    $response->assertStatus(429);
});

test('authenticated users cannot access magic link request page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/magic-link');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('magic link mail contains correct signed url', function (): void {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/magic-link', ['email' => $user->email]);

    Mail::assertQueued(MagicLinkMail::class, fn (MagicLinkMail $mail): bool => str_contains($mail->url, 'magic-link/login/'.$user->reference_id)
        && str_contains($mail->url, 'signature='));
});
