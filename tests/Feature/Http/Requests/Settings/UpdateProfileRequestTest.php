<?php

declare(strict_types=1);

use App\Enums\FieldType;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Models\Field;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

describe('UpdateProfileRequest validation', function (): void {
    test('validation passes with valid name', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'ValidName',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when name is missing', function (): void {
        $request = new UpdateProfileRequest([
            'name' => '',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('name'))->toBeTrue();
    });

    test('validation fails when name is too short', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'A',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('name'))->toBeTrue();
    });

    test('validation fails when name is too long', function (): void {
        $request = new UpdateProfileRequest([
            'name' => str_repeat('A', 33),
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('name'))->toBeTrue();
    });

    test('validation passes when name is at minimum length', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'AB',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation passes when name is at maximum length', function (): void {
        $request = new UpdateProfileRequest([
            'name' => str_repeat('A', 32),
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation passes with null signature', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'ValidName',
            'signature' => null,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation passes with valid signature', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'ValidName',
            'signature' => 'This is my signature',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when signature is too long', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'ValidName',
            'signature' => str_repeat('A', 501),
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('signature'))->toBeTrue();
    });

    test('validation passes when signature is at maximum length', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'ValidName',
            'signature' => str_repeat('A', 500),
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when name is not a string', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 12345,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('name'))->toBeTrue();
    });

    test('validation fails when signature is not a string', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'ValidName',
            'signature' => ['array', 'not', 'string'],
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('signature'))->toBeTrue();
    });
});

describe('UpdateProfileRequest avatar validation', function (): void {
    test('validation passes with valid image avatar', function (): void {
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    test('validation passes with null avatar', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'ValidName',
            'avatar' => null,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when avatar is not an image', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'avatar' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertSessionHasErrors(['avatar']);
    });

    test('validation fails when avatar exceeds max size', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(3000),
        ]);

        $response->assertSessionHasErrors(['avatar']);
    });

    test('validation passes when avatar is at max size', function (): void {
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(2048),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });
});

describe('UpdateProfileRequest custom fields validation', function (): void {
    test('validation passes with valid number field', function (): void {
        $field = Field::factory()->create([
            'name' => 'age',
            'label' => 'Age',
            'type' => FieldType::Number,
            'is_required' => false,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'fields' => [
                $field->id => '25',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    test('validation fails when number field contains non-numeric value', function (): void {
        $field = Field::factory()->create([
            'name' => 'age',
            'label' => 'Age',
            'type' => FieldType::Number,
            'is_required' => false,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'fields' => [
                $field->id => 'not-a-number',
            ],
        ]);

        $response->assertSessionHasErrors(['fields.'.$field->id]);
    });

    test('validation fails when required field is missing', function (): void {
        $field = Field::factory()->create([
            'name' => 'required_field',
            'label' => 'Required Field',
            'type' => FieldType::Number,
            'is_required' => true,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'fields' => [
                $field->id => '',
            ],
        ]);

        $response->assertSessionHasErrors(['fields.'.$field->id]);
    });

    test('validation passes when required field is provided', function (): void {
        $field = Field::factory()->create([
            'name' => 'required_field',
            'label' => 'Required Field',
            'type' => FieldType::Number,
            'is_required' => true,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'fields' => [
                $field->id => '42',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    test('validation passes with valid date field', function (): void {
        $field = Field::factory()->create([
            'name' => 'birthdate',
            'label' => 'Birthdate',
            'type' => FieldType::Date,
            'is_required' => false,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'fields' => [
                $field->id => '2000-01-15',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    test('validation fails when date field contains invalid date', function (): void {
        $field = Field::factory()->create([
            'name' => 'birthdate',
            'label' => 'Birthdate',
            'type' => FieldType::Date,
            'is_required' => false,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'fields' => [
                $field->id => 'not-a-date',
            ],
        ]);

        $response->assertSessionHasErrors(['fields.'.$field->id]);
    });

    test('validation passes with valid datetime field', function (): void {
        $field = Field::factory()->create([
            'name' => 'event_time',
            'label' => 'Event Time',
            'type' => FieldType::DateTime,
            'is_required' => false,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'fields' => [
                $field->id => '2025-01-15 14:30:00',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    test('validation passes when optional checkbox field is unchecked', function (): void {
        $field = Field::factory()->create([
            'name' => 'optional_checkbox',
            'label' => 'Optional Checkbox',
            'type' => FieldType::Checkbox,
            'is_required' => false,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
            'fields' => [
                $field->id => '',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    test('validation passes when no fields are submitted', function (): void {
        Field::factory()->create([
            'name' => 'some_field',
            'label' => 'Some Field',
            'type' => FieldType::Text,
            'is_required' => false,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/account', [
            'name' => 'ValidName',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });
});

describe('UpdateProfileRequest custom messages', function (): void {
    test('name required message is customized', function (): void {
        $request = new UpdateProfileRequest([
            'name' => '',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('name'))->toBe('Please provide your username.');
    });

    test('signature max message is customized', function (): void {
        $request = new UpdateProfileRequest([
            'name' => 'ValidName',
            'signature' => str_repeat('A', 501),
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('signature'))->toBe('Your signature cannot be longer than 500 characters.');
    });

    test('avatar image message is customized', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/settings/account')
            ->post('/settings/account', [
                'name' => 'ValidName',
                'avatar' => UploadedFile::fake()->create('document.pdf', 100),
            ]);

        $response->assertSessionHasErrors(['avatar' => 'The avatar must be an image file.']);
    });

    test('avatar max size message is customized', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/settings/account')
            ->post('/settings/account', [
                'name' => 'ValidName',
                'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(3000),
            ]);

        $response->assertSessionHasErrors(['avatar' => 'The avatar file size cannot exceed 2MB.']);
    });
});

describe('UpdateProfileRequest custom attributes', function (): void {
    test('name attribute is customized to username', function (): void {
        $request = new UpdateProfileRequest;

        expect($request->attributes()['name'])->toBe('username');
    });

    test('signature attribute is customized', function (): void {
        $request = new UpdateProfileRequest;

        expect($request->attributes()['signature'])->toBe('signature');
    });

    test('avatar attribute is customized', function (): void {
        $request = new UpdateProfileRequest;

        expect($request->attributes()['avatar'])->toBe('avatar');
    });

    test('field attributes use lowercase field labels', function (): void {
        $field = Field::factory()->create([
            'name' => 'test_field',
            'label' => 'Test Field Label',
            'type' => FieldType::Text,
        ]);

        $request = new UpdateProfileRequest;

        expect($request->attributes()['fields.'.$field->id])->toBe('test field label');
    });
});

describe('UpdateProfileRequest authorization', function (): void {
    test('authorize returns true when user is authenticated', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $request = new UpdateProfileRequest;

        expect($request->authorize())->toBeTrue();

        Auth::logout();
    });

    test('authorize returns false when user is guest', function (): void {
        $request = new UpdateProfileRequest;

        expect($request->authorize())->toBeFalse();
    });
});
