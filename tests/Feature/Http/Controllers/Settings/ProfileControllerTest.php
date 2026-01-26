<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile settings page is displayed for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/account');

    $response->assertOk();
});

test('profile settings page shows custom fields', function (): void {
    $user = User::factory()->create();
    $field = Field::factory()->create([
        'name' => 'location',
        'label' => 'Location',
    ]);

    $response = $this->actingAs($user)->get('/settings/account');

    $response->assertOk();
});

test('profile settings page redirects guests to login', function (): void {
    $response = $this->get('/settings/account');

    $response->assertRedirect('/login');
});

test('profile can be updated with valid data', function (): void {
    $user = User::factory()->create([
        'name' => 'Original Name',
    ]);

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => 'Updated Name',
        'signature' => 'My new signature',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message');

    expect($user->fresh())
        ->name->toBe('Updated Name')
        ->signature->toBe('My new signature');
});

test('profile can be updated with avatar', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => $user->name,
        'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
    ]);

    $response->assertRedirect();

    expect($user->fresh()->avatar)->not->toBeNull();
});

test('profile update fails with missing name', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => '',
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('profile update fails with name too short', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => 'A',
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('profile update fails with name too long', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => str_repeat('A', 33),
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('profile update fails with signature too long', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => $user->name,
        'signature' => str_repeat('A', 501),
    ]);

    $response->assertSessionHasErrors(['signature']);
});

test('profile update fails with invalid avatar type', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => $user->name,
        'avatar' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    $response->assertSessionHasErrors(['avatar']);
});

test('profile update fails with avatar too large', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => $user->name,
        'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(3000),
    ]);

    $response->assertSessionHasErrors(['avatar']);
});

test('profile can be updated with custom fields', function (): void {
    $user = User::factory()->create();
    $field = Field::factory()->create([
        'name' => 'age',
        'label' => 'Age',
        'type' => App\Enums\FieldType::Number,
        'is_required' => false,
    ]);

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => $user->name,
        'fields' => [
            $field->id => '25',
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $user->refresh()->load('fields');
    $userField = $user->fields->firstWhere('id', $field->id);
    expect($userField)->not->toBeNull();
    expect($userField->pivot->value)->toBe('25');
});

test('profile update is forbidden for guests', function (): void {
    $response = $this->post('/settings/account', [
        'name' => 'Test Name',
    ]);

    $response->assertRedirect('/login');
});

test('account can be deleted', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete('/settings/account');

    $response->assertRedirect(route('login', absolute: false));

    expect(User::find($user->id))->toBeNull();
});

test('account deletion redirects guests to login', function (): void {
    $response = $this->delete('/settings/account');

    $response->assertRedirect('/login');
});

test('profile update clears signature when set to null', function (): void {
    $user = User::factory()->create([
        'signature' => 'Original signature',
    ]);

    $response = $this->actingAs($user)->post('/settings/account', [
        'name' => $user->name,
        'signature' => null,
    ]);

    $response->assertRedirect();

    expect($user->fresh()->signature)->toBeNull();
});
