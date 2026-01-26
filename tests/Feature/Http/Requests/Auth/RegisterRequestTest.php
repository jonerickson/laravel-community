<?php

declare(strict_types=1);

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Policy;
use App\Models\PolicyCategory;
use App\Models\User;
use App\Settings\RegistrationSettings;

describe('RegisterRequest validation', function (): void {
    test('validation passes with valid data', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when name is missing', function (): void {
        $request = new RegisterRequest([
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('name'))->toBeTrue();
    });

    test('validation fails when name is too short', function (): void {
        $request = new RegisterRequest([
            'name' => 'a',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('name'))->toBeTrue();
    });

    test('validation fails when name is too long', function (): void {
        $request = new RegisterRequest([
            'name' => str_repeat('a', 33),
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('name'))->toBeTrue();
    });

    test('validation fails when email is missing', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('email'))->toBeTrue();
    });

    test('validation fails when email is not valid format', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('email'))->toBeTrue();
    });

    test('validation fails when email contains uppercase letters', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'Test@Example.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('email'))->toBeTrue();
    });

    test('validation fails when email is too long', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => str_repeat('a', 250).'@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('email'))->toBeTrue();
    });

    test('validation fails when email is already registered', function (): void {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('email'))->toBeTrue();
    });

    test('validation fails when password is missing', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();
    });

    test('validation fails when password confirmation does not match', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();
    });

    test('validation fails when password confirmation is missing', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();
    });

    test('validation fails when password is too short', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('password'))->toBeTrue();
    });

    test('validation passes with minimum name length', function (): void {
        $request = new RegisterRequest([
            'name' => 'ab',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation passes with maximum name length', function (): void {
        $request = new RegisterRequest([
            'name' => str_repeat('a', 32),
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation passes with minimum password length', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });
});

describe('RegisterRequest required policies', function (): void {
    test('validation fails when required policy is not accepted', function (): void {
        $category = PolicyCategory::factory()->create(['is_active' => true]);
        $policy = Policy::factory()->create([
            'is_active' => true,
            'policy_category_id' => $category->id,
        ]);

        app(RegistrationSettings::class)->required_policy_ids = [$policy->id];

        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('policy.'.$policy->id))->toBeTrue();
    });

    test('validation passes when required policy is accepted', function (): void {
        $category = PolicyCategory::factory()->create(['is_active' => true]);
        $policy = Policy::factory()->create([
            'is_active' => true,
            'policy_category_id' => $category->id,
        ]);

        app(RegistrationSettings::class)->required_policy_ids = [$policy->id];

        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'policy' => [
                $policy->id => 'on',
            ],
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when some required policies are not accepted', function (): void {
        $category = PolicyCategory::factory()->create(['is_active' => true]);
        $policy1 = Policy::factory()->create([
            'is_active' => true,
            'policy_category_id' => $category->id,
        ]);
        $policy2 = Policy::factory()->create([
            'is_active' => true,
            'policy_category_id' => $category->id,
        ]);

        app(RegistrationSettings::class)->required_policy_ids = [$policy1->id, $policy2->id];

        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'policy' => [
                $policy1->id => 'on',
            ],
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('policy.'.$policy2->id))->toBeTrue();
        expect($validator->errors()->has('policy.'.$policy1->id))->toBeFalse();
    });

    test('validation passes when all required policies are accepted', function (): void {
        $category = PolicyCategory::factory()->create(['is_active' => true]);
        $policy1 = Policy::factory()->create([
            'is_active' => true,
            'policy_category_id' => $category->id,
        ]);
        $policy2 = Policy::factory()->create([
            'is_active' => true,
            'policy_category_id' => $category->id,
        ]);

        app(RegistrationSettings::class)->required_policy_ids = [$policy1->id, $policy2->id];

        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'policy' => [
                $policy1->id => 'on',
                $policy2->id => 'on',
            ],
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation passes when no policies are required', function (): void {
        app(RegistrationSettings::class)->required_policy_ids = [];

        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });
});

describe('RegisterRequest custom messages', function (): void {
    test('custom error message for missing name', function (): void {
        $request = new RegisterRequest([
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('name'))->toBe('Please enter your username.');
    });

    test('custom error message for missing email', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('email'))->toBe('Please enter your email address.');
    });

    test('custom error message for invalid email format', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('email'))->toBe('Please enter a valid email address.');
    });

    test('custom error message for duplicate email', function (): void {
        User::factory()->create(['email' => 'existing@example.com']);

        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('email'))->toBe('This email is already registered.');
    });

    test('custom error message for missing password', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('password'))->toBe('Please create a password.');
    });

    test('custom error message for password confirmation mismatch', function (): void {
        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('password'))->toBe('The password confirmation does not match.');
    });

    test('custom error message for required policy', function (): void {
        $category = PolicyCategory::factory()->create(['is_active' => true]);
        $policy = Policy::factory()->create([
            'is_active' => true,
            'policy_category_id' => $category->id,
        ]);

        app(RegistrationSettings::class)->required_policy_ids = [$policy->id];

        $request = new RegisterRequest([
            'name' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('policy.'.$policy->id))->toBe('You must agree to each policy to continue.');
    });
});

describe('RegisterRequest custom attributes', function (): void {
    test('name attribute is displayed as username', function (): void {
        $request = new RegisterRequest;

        expect($request->attributes())->toBe(['name' => 'username']);
    });
});

describe('RegisterRequest authorization', function (): void {
    test('authorize returns true', function (): void {
        $request = new RegisterRequest;

        expect($request->authorize())->toBeTrue();
    });
});
