<?php

declare(strict_types=1);

use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

describe('UpdatePasswordRequest validation', function (): void {
    test('validation passes with valid password and confirmation', function (): void {
        $user = User::factory()->create([
            'password' => null,
        ]);
        Auth::login($user);

        $request = UpdatePasswordRequest::create('/settings/password', 'PUT', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $request->setUserResolver(fn () => $user);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();

        Auth::logout();
    });

    test('validation fails when password is missing', function (): void {
        $user = User::factory()->create([
            'password' => null,
        ]);
        Auth::login($user);

        $request = UpdatePasswordRequest::create('/settings/password', 'PUT', [
            'password_confirmation' => 'NewPassword123!',
        ]);
        $request->setUserResolver(fn () => $user);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();

        Auth::logout();
    });

    test('validation fails when password confirmation is missing', function (): void {
        $user = User::factory()->create([
            'password' => null,
        ]);
        Auth::login($user);

        $request = UpdatePasswordRequest::create('/settings/password', 'PUT', [
            'password' => 'NewPassword123!',
        ]);
        $request->setUserResolver(fn () => $user);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();

        Auth::logout();
    });

    test('validation fails when passwords do not match', function (): void {
        $user = User::factory()->create([
            'password' => null,
        ]);
        Auth::login($user);

        $request = UpdatePasswordRequest::create('/settings/password', 'PUT', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword456!',
        ]);
        $request->setUserResolver(fn () => $user);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();

        Auth::logout();
    });

    test('validation fails when password is too short', function (): void {
        $user = User::factory()->create([
            'password' => null,
        ]);
        Auth::login($user);

        $request = UpdatePasswordRequest::create('/settings/password', 'PUT', [
            'password' => 'Short1!',
            'password_confirmation' => 'Short1!',
        ]);
        $request->setUserResolver(fn () => $user);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();

        Auth::logout();
    });
});

describe('UpdatePasswordRequest current password validation', function (): void {
    test('validation requires current_password when user has existing password', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('ExistingPassword123!'),
        ]);
        Auth::login($user);

        $request = UpdatePasswordRequest::create('/settings/password', 'PUT', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $request->setUserResolver(fn () => $user);

        $rules = $request->rules();

        expect($rules)->toHaveKey('current_password');
        expect($rules['current_password'])->toContain('required');
        expect($rules['current_password'])->toContain('current_password');

        Auth::logout();
    });

    test('validation does not require current_password when user has no password', function (): void {
        $user = User::factory()->create([
            'password' => null,
        ]);
        Auth::login($user);

        $request = UpdatePasswordRequest::create('/settings/password', 'PUT', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $request->setUserResolver(fn () => $user);

        $rules = $request->rules();

        expect($rules)->not->toHaveKey('current_password');

        Auth::logout();
    });

    test('validation passes with correct current_password for user with existing password', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('ExistingPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'ExistingPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    test('validation fails with incorrect current_password for user with existing password', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('ExistingPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'WrongPassword!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    });

    test('validation fails with missing current_password for user with existing password', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('ExistingPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/settings/password', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    });

    test('validation passes without current_password for user without existing password', function (): void {
        $user = User::factory()->create([
            'password' => null,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->put('/settings/password', [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });
});

describe('UpdatePasswordRequest custom messages', function (): void {
    test('current_password required message is customized', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('ExistingPassword123!'),
        ]);

        $response = $this->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertSessionHasErrors([
            'current_password' => 'Please provide your current password.',
        ]);
    });

    test('current_password incorrect message is customized', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('ExistingPassword123!'),
        ]);

        $response = $this->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'WrongPassword!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertSessionHasErrors([
            'current_password' => 'The current password is incorrect.',
        ]);
    });

    test('password required message is customized', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('ExistingPassword123!'),
        ]);

        $response = $this->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'ExistingPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertSessionHasErrors([
            'password' => 'Please provide a new password.',
        ]);
    });

    test('password confirmed message is customized', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('ExistingPassword123!'),
        ]);

        $response = $this->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'ExistingPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'DifferentPassword456!',
            ]);

        $response->assertSessionHasErrors([
            'password' => 'The password confirmation does not match.',
        ]);
    });
});

describe('UpdatePasswordRequest authorization', function (): void {
    test('authorize returns true when user is authenticated', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $request = new UpdatePasswordRequest;

        expect($request->authorize())->toBeTrue();

        Auth::logout();
    });

    test('authorize returns false when user is guest', function (): void {
        $request = new UpdatePasswordRequest;

        expect($request->authorize())->toBeFalse();
    });
});

describe('UpdatePasswordRequest HTTP layer', function (): void {
    test('password is updated successfully', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
    });

    test('password is hashed after update', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $updatedUser = $user->fresh();

        expect($updatedUser->password)->not->toBe('NewPassword123!');
        expect(Hash::check('NewPassword123!', $updatedUser->password))->toBeTrue();
    });

    test('guests cannot update password', function (): void {
        $response = $this->put('/settings/password', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect('/login');
    });

    test('passwordless user is redirected to set-password page when updating password', function (): void {
        $user = User::factory()->create([
            'password' => null,
        ]);

        $response = $this->actingAs($user)->put('/settings/password', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect(route('set-password.notice'));
    });
});
