<?php

declare(strict_types=1);

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

describe('LoginRequest validation', function (): void {
    test('validation passes with valid email and password', function (): void {
        $request = new LoginRequest([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when email is missing', function (): void {
        $request = new LoginRequest([
            'password' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('email'))->toBeTrue();
    });

    test('validation fails when password is missing', function (): void {
        $request = new LoginRequest([
            'email' => 'test@example.com',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();
    });

    test('validation fails when email is not a valid email format', function (): void {
        $request = new LoginRequest([
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('email'))->toBeTrue();
    });

    test('validation passes with redirect parameter', function (): void {
        $request = new LoginRequest([
            'email' => 'test@example.com',
            'password' => 'password123',
            'redirect' => '/settings/profile',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation passes with null redirect parameter', function (): void {
        $request = new LoginRequest([
            'email' => 'test@example.com',
            'password' => 'password123',
            'redirect' => null,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when email is not a string', function (): void {
        $request = new LoginRequest([
            'email' => 12345,
            'password' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('email'))->toBeTrue();
    });

    test('validation fails when password is not a string', function (): void {
        $request = new LoginRequest([
            'email' => 'test@example.com',
            'password' => 12345,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();
    });
});

describe('LoginRequest authenticate method', function (): void {
    test('authenticate succeeds with valid credentials', function (): void {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    });

    test('authenticate fails with invalid password', function (): void {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });

    test('authenticate fails with non-existent email', function (): void {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });

    test('authenticate fails for user without password', function (): void {
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

    test('authenticate respects remember me option', function (): void {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
            'remember' => true,
        ]);

        $this->assertAuthenticatedAs($user);
    });
});

describe('LoginRequest rate limiting', function (): void {
    test('rate limiting triggers after 5 failed attempts', function (): void {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

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

    test('rate limiter is cleared after successful authentication', function (): void {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);

        Auth::logout();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
    });

    test('throttle key is based on email and ip', function (): void {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'Test@Example.COM',
            'password' => 'password123',
        ]);

        $request->setContainer(app());

        $throttleKey = $request->throttleKey();

        expect($throttleKey)->toContain('test@example.com');
        expect($throttleKey)->toContain('|');
    });

    test('ensureIsNotRateLimited passes when not rate limited', function (): void {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $request->setContainer(app());

        $request->ensureIsNotRateLimited();

        expect(true)->toBeTrue();
    });
});

describe('LoginRequest authorization', function (): void {
    test('authorize returns true', function (): void {
        $request = new LoginRequest;

        expect($request->authorize())->toBeTrue();
    });
});
