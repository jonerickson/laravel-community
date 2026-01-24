<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();

    // Create roles and permissions
    $this->userRole = Role::create(['name' => 'user']);
    $this->moderatorRole = Role::create(['name' => 'moderator']);

    $this->readPermission = Permission::create(['name' => 'read posts']);
    $this->writePermission = Permission::create(['name' => 'write posts']);

    // Assign permissions to roles
    $this->userRole->givePermissionTo($this->readPermission);
    $this->moderatorRole->givePermissionTo([$this->readPermission, $this->writePermission]);
});

it('can check roles inherited from groups', function (): void {
    $this->group->assignRole($this->moderatorRole);
    $this->user->groups()->attach($this->group);

    expect($this->user->hasRole('moderator'))->toBeTrue();
    expect($this->user->hasRole('admin'))->toBeFalse();
});

it('can check permissions inherited from group roles', function (): void {
    $this->group->assignRole($this->moderatorRole);
    $this->user->groups()->attach($this->group);

    expect($this->user->hasPermissionTo('read posts'))->toBeTrue();
    expect($this->user->hasPermissionTo('write posts'))->toBeTrue();
});

it('can check direct group permissions', function (): void {
    $this->group->givePermissionTo($this->writePermission);
    $this->user->groups()->attach($this->group);

    expect($this->user->hasPermissionTo('write posts'))->toBeTrue();
    expect($this->user->hasPermissionTo('read posts'))->toBeFalse();
});

it('combines direct and group roles/permissions', function (): void {
    // Direct role
    $this->user->assignRole($this->userRole);

    // Group role
    $this->group->assignRole($this->moderatorRole);
    $this->user->groups()->attach($this->group);

    expect($this->user->hasRole('user'))->toBeTrue();
    expect($this->user->hasRole('moderator'))->toBeTrue();

    expect($this->user->hasPermissionTo('read posts'))->toBeTrue();
    expect($this->user->hasPermissionTo('write posts'))->toBeTrue();
});

it('can get all roles including from groups', function (): void {
    $this->user->assignRole($this->userRole);
    $this->group->assignRole($this->moderatorRole);
    $this->user->groups()->attach($this->group);

    $allRoles = $this->user->getAllRoles();

    expect($allRoles->pluck('name')->toArray())->toContain('user')
        ->and($allRoles->pluck('name')->toArray())->toContain('moderator')
        ->and($allRoles->count())->toBe(2);
});

it('handles hasAllRoles correctly', function (): void {
    // Direct role
    $this->user->assignRole($this->userRole);

    // Group role
    $this->group->assignRole($this->moderatorRole);
    $this->user->groups()->attach($this->group);

    expect($this->user->hasAllRoles(['user', 'moderator']))->toBeTrue();
    expect($this->user->hasAllRoles(['user', 'admin']))->toBeFalse();
});
