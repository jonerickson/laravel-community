<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be changed with correct current password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $response = $this->actingAs($user)->put('/settings/password', [
        'current_password' => 'current-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Password updated successfully.');

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

test('password change fails with incorrect current password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $response = $this->actingAs($user)->put('/settings/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSessionHasErrors(['current_password']);

    expect(Hash::check('current-password', $user->fresh()->password))->toBeTrue();
});

test('password change fails when new passwords do not match', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $response = $this->actingAs($user)->put('/settings/password', [
        'current_password' => 'current-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors(['password']);

    expect(Hash::check('current-password', $user->fresh()->password))->toBeTrue();
});

test('password change fails with missing current password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $response = $this->actingAs($user)->put('/settings/password', [
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSessionHasErrors(['current_password']);
});

test('password change fails with missing new password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $response = $this->actingAs($user)->put('/settings/password', [
        'current_password' => 'current-password',
    ]);

    $response->assertSessionHasErrors(['password']);
});

test('password change fails with missing password confirmation', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $response = $this->actingAs($user)->put('/settings/password', [
        'current_password' => 'current-password',
        'password' => 'new-password-123',
    ]);

    $response->assertSessionHasErrors(['password']);
});

test('passwordless user is redirected to set password page', function (): void {
    $user = User::factory()->create([
        'password' => null,
    ]);

    $response = $this->actingAs($user)->put('/settings/password', [
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect(route('set-password.notice'));

    expect($user->fresh()->password)->toBeNull();
});

test('password update redirects guests to login', function (): void {
    $response = $this->put('/settings/password', [
        'current_password' => 'current-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect('/login');
});

test('password is hashed when stored', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $this->actingAs($user)->put('/settings/password', [
        'current_password' => 'current-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $user->refresh();

    expect($user->password)->not->toBe('new-password-123');
    expect(Hash::check('new-password-123', $user->password))->toBeTrue();
});
