<?php

declare(strict_types=1);

use App\Data\CustomerData;
use App\Managers\PaymentManager;
use App\Models\User;

test('billing settings page is displayed for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/billing');

    $response->assertOk();
});

test('billing settings page redirects guests to login', function (): void {
    $response = $this->get('/settings/billing');

    $response->assertRedirect('/login');
});

test('billing settings page shows user billing data', function (): void {
    $user = User::factory()->create([
        'billing_address' => '123 Main St',
        'billing_city' => 'New York',
        'billing_state' => 'NY',
        'billing_postal_code' => '10001',
        'billing_country' => 'US',
    ]);

    $response = $this->actingAs($user)->get('/settings/billing');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/billing')
        ->has('user')
        ->where('user.billing_address', '123 Main St')
        ->where('user.billing_city', 'New York')
        ->where('user.billing_state', 'NY')
        ->where('user.billing_postal_code', '10001')
        ->where('user.billing_country', 'US')
    );
});

test('billing can be updated with valid data', function (): void {
    $user = User::factory()->create();

    $this->mock(PaymentManager::class, function ($mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(CustomerData::from([
                'id' => 'cus_123',
                'email' => $user->email,
            ]));
        $mock->shouldReceive('syncCustomerInformation')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(true);
    });

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '456 Oak Ave',
        'billing_city' => 'Los Angeles',
        'billing_state' => 'CA',
        'billing_postal_code' => '90001',
        'billing_country' => 'US',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Your billing information was updated successfully.');

    expect($user->fresh())
        ->billing_address->toBe('456 Oak Ave')
        ->billing_city->toBe('Los Angeles')
        ->billing_state->toBe('CA')
        ->billing_postal_code->toBe('90001')
        ->billing_country->toBe('US');
});

test('billing update creates customer if not exists', function (): void {
    $user = User::factory()->create();

    $this->mock(PaymentManager::class, function ($mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(null);
        $mock->shouldReceive('createCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(true);
        $mock->shouldReceive('syncCustomerInformation')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(true);
    });

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '789 Pine St',
        'billing_city' => 'Chicago',
        'billing_state' => 'IL',
        'billing_postal_code' => '60601',
        'billing_country' => 'US',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Your billing information was updated successfully.');
});

test('billing update shows error when customer creation fails', function (): void {
    $user = User::factory()->create();

    $this->mock(PaymentManager::class, function ($mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(null);
        $mock->shouldReceive('createCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(false);
    });

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '789 Pine St',
        'billing_city' => 'Chicago',
        'billing_state' => 'IL',
        'billing_postal_code' => '60601',
        'billing_country' => 'US',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'We were unable to sync your billing data. Please try again.');
    $response->assertSessionHas('messageVariant', 'error');
});

test('billing update fails with missing address', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '',
        'billing_city' => 'New York',
        'billing_state' => 'NY',
        'billing_postal_code' => '10001',
        'billing_country' => 'US',
    ]);

    $response->assertSessionHasErrors(['billing_address']);
});

test('billing update fails with missing city', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '123 Main St',
        'billing_city' => '',
        'billing_state' => 'NY',
        'billing_postal_code' => '10001',
        'billing_country' => 'US',
    ]);

    $response->assertSessionHasErrors(['billing_city']);
});

test('billing update fails with missing state', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '123 Main St',
        'billing_city' => 'New York',
        'billing_state' => '',
        'billing_postal_code' => '10001',
        'billing_country' => 'US',
    ]);

    $response->assertSessionHasErrors(['billing_state']);
});

test('billing update fails with missing postal code', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '123 Main St',
        'billing_city' => 'New York',
        'billing_state' => 'NY',
        'billing_postal_code' => '',
        'billing_country' => 'US',
    ]);

    $response->assertSessionHasErrors(['billing_postal_code']);
});

test('billing update fails with missing country', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '123 Main St',
        'billing_city' => 'New York',
        'billing_state' => 'NY',
        'billing_postal_code' => '10001',
        'billing_country' => '',
    ]);

    $response->assertSessionHasErrors(['billing_country']);
});

test('billing update fails with invalid country code length', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '123 Main St',
        'billing_city' => 'New York',
        'billing_state' => 'NY',
        'billing_postal_code' => '10001',
        'billing_country' => 'USA',
    ]);

    $response->assertSessionHasErrors(['billing_country']);
});

test('billing update accepts optional address line 2', function (): void {
    $user = User::factory()->create();

    $this->mock(PaymentManager::class, function ($mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(CustomerData::from([
                'id' => 'cus_123',
                'email' => $user->email,
            ]));
        $mock->shouldReceive('syncCustomerInformation')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(true);
    });

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '123 Main St',
        'billing_address_line_2' => 'Suite 100',
        'billing_city' => 'New York',
        'billing_state' => 'NY',
        'billing_postal_code' => '10001',
        'billing_country' => 'US',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect($user->fresh())
        ->billing_address_line_2->toBe('Suite 100');
});

test('billing update accepts optional vat id', function (): void {
    $user = User::factory()->create();

    $this->mock(PaymentManager::class, function ($mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(CustomerData::from([
                'id' => 'cus_123',
                'email' => $user->email,
            ]));
        $mock->shouldReceive('syncCustomerInformation')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(true);
    });

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '123 Main St',
        'billing_city' => 'London',
        'billing_state' => 'London',
        'billing_postal_code' => 'SW1A 1AA',
        'billing_country' => 'GB',
        'vat_id' => 'GB123456789',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect($user->fresh())
        ->vat_id->toBe('GB123456789');
});

test('billing update accepts optional extra billing information', function (): void {
    $user = User::factory()->create();

    $this->mock(PaymentManager::class, function ($mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(CustomerData::from([
                'id' => 'cus_123',
                'email' => $user->email,
            ]));
        $mock->shouldReceive('syncCustomerInformation')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->once()
            ->andReturn(true);
    });

    $response = $this->actingAs($user)->post('/settings/billing', [
        'billing_address' => '123 Main St',
        'billing_city' => 'New York',
        'billing_state' => 'NY',
        'billing_postal_code' => '10001',
        'billing_country' => 'US',
        'extra_billing_information' => 'Please include PO number 12345 on all invoices.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect($user->fresh())
        ->extra_billing_information->toBe('Please include PO number 12345 on all invoices.');
});

test('billing update is forbidden for guests', function (): void {
    $response = $this->post('/settings/billing', [
        'billing_address' => '123 Main St',
        'billing_city' => 'New York',
        'billing_state' => 'NY',
        'billing_postal_code' => '10001',
        'billing_country' => 'US',
    ]);

    $response->assertRedirect('/login');
});
