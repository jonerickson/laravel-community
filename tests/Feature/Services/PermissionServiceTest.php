<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\User;
use App\Services\PermissionService;
use Spatie\Permission\Models\Permission;

describe('hasPermissionTo with null user (guest)', function (): void {
    test('returns true when default guest group has the permission', function (): void {
        $permission = Permission::findOrCreate('view-public-content', 'web');

        $guestGroup = Group::factory()->asDefaultGuest()->create();
        $guestGroup->givePermissionTo($permission);

        $result = PermissionService::hasPermissionTo('view-public-content');

        expect($result)->toBeTrue();
    });

    test('returns false when default guest group does not have the permission', function (): void {
        $permission = Permission::findOrCreate('view-admin-dashboard', 'web');

        $guestGroup = Group::factory()->asDefaultGuest()->create();
        // Don't give the permission to the guest group

        $result = PermissionService::hasPermissionTo('view-admin-dashboard');

        expect($result)->toBeFalse();
    });

    test('returns false when no default guest group exists', function (): void {
        // Create the permission first (Spatie throws exception if permission doesn't exist)
        Permission::findOrCreate('some-permission', 'web');

        // Ensure no default guest group exists (none created, default member group exists from Pest.php)
        $result = PermissionService::hasPermissionTo('some-permission');

        expect($result)->toBeFalse();
    });

    test('returns false when guest group does not have any permissions assigned', function (): void {
        // Create permission and guest group, but don't assign permission to group
        Permission::findOrCreate('unused-permission', 'web');
        Group::factory()->asDefaultGuest()->create();

        $result = PermissionService::hasPermissionTo('unused-permission');

        expect($result)->toBeFalse();
    });
});

describe('hasPermissionTo with authenticated user', function (): void {
    test('returns true when user has direct permission', function (): void {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('edit-own-profile', 'web');
        $user->givePermissionTo($permission);

        $result = PermissionService::hasPermissionTo('edit-own-profile', $user);

        expect($result)->toBeTrue();
    });

    test('returns true when user permission through role', function (): void {
        $user = User::factory()->create();
        $role = Spatie\Permission\Models\Role::findOrCreate('editor', 'web');
        $permission = Permission::findOrCreate('edit-posts', 'web');
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $result = PermissionService::hasPermissionTo('edit-posts', $user);

        expect($result)->toBeTrue();
    });

    test('returns true when user group has the permission', function (): void {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $permission = Permission::findOrCreate('create-topics', 'web');
        $group->givePermissionTo($permission);
        $user->groups()->attach($group);

        $result = PermissionService::hasPermissionTo('create-topics', $user);

        expect($result)->toBeTrue();
    });

    test('returns true when any of multiple groups has the permission', function (): void {
        $user = User::factory()->create();
        $groupWithoutPermission = Group::factory()->create();
        $groupWithPermission = Group::factory()->create();
        $permission = Permission::findOrCreate('moderate-content', 'web');
        $groupWithPermission->givePermissionTo($permission);

        $user->groups()->attach([$groupWithoutPermission->id, $groupWithPermission->id]);

        $result = PermissionService::hasPermissionTo('moderate-content', $user);

        expect($result)->toBeTrue();
    });

    test('returns false when user has no permission directly or through groups', function (): void {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $user->groups()->attach($group);
        // Create permission but don't give it to user or group
        Permission::findOrCreate('admin-only-permission', 'web');

        $result = PermissionService::hasPermissionTo('admin-only-permission', $user);

        expect($result)->toBeFalse();
    });

    test('returns false when user has no groups and no direct permission', function (): void {
        $user = User::factory()->create();
        // Detach all groups
        $user->groups()->detach();
        // Create permission but don't give it to user
        Permission::findOrCreate('some-other-permission', 'web');

        $result = PermissionService::hasPermissionTo('some-other-permission', $user);

        expect($result)->toBeFalse();
    });

    test('checks user direct permission before group permissions', function (): void {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('special-permission', 'web');
        $user->givePermissionTo($permission);

        // Also create a group without the permission
        $group = Group::factory()->create();
        $user->groups()->attach($group);

        $result = PermissionService::hasPermissionTo('special-permission', $user);

        expect($result)->toBeTrue();
    });

    test('iterates through all groups until permission is found', function (): void {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('last-group-permission', 'web');

        // Create 3 groups, only the last one has the permission
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();
        $group3 = Group::factory()->create();
        $group3->givePermissionTo($permission);

        $user->groups()->attach([$group1->id, $group2->id, $group3->id]);

        $result = PermissionService::hasPermissionTo('last-group-permission', $user);

        expect($result)->toBeTrue();
    });
});

describe('hasPermissionTo with different permission names', function (): void {
    test('handles wildcard permissions', function (): void {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('posts.*', 'web');
        $user->givePermissionTo($permission);

        // Spatie permissions with wildcard - check the wildcard itself
        $result = PermissionService::hasPermissionTo('posts.*', $user);

        expect($result)->toBeTrue();
    });

    test('handles permission with special characters', function (): void {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('view:admin-panel', 'web');
        $user->givePermissionTo($permission);

        $result = PermissionService::hasPermissionTo('view:admin-panel', $user);

        expect($result)->toBeTrue();
    });

    test('handles permission with dots', function (): void {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('forums.topics.create', 'web');
        $user->givePermissionTo($permission);

        $result = PermissionService::hasPermissionTo('forums.topics.create', $user);

        expect($result)->toBeTrue();
    });
});

describe('hasPermissionTo with group roles', function (): void {
    test('returns true when group has permission through role', function (): void {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $role = Spatie\Permission\Models\Role::findOrCreate('moderator', 'web');
        $permission = Permission::findOrCreate('ban-users', 'web');
        $role->givePermissionTo($permission);
        $group->assignRole($role);
        $user->groups()->attach($group);

        $result = PermissionService::hasPermissionTo('ban-users', $user);

        expect($result)->toBeTrue();
    });
});

describe('hasPermissionTo priority', function (): void {
    test('user direct permission takes priority over guest group', function (): void {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('priority-test-permission', 'web');

        // Give permission to user directly
        $user->givePermissionTo($permission);

        // Create guest group without the permission
        Group::factory()->asDefaultGuest()->create();

        $result = PermissionService::hasPermissionTo('priority-test-permission', $user);

        expect($result)->toBeTrue();
    });

    test('guest group is only checked when user is null', function (): void {
        $permission = Permission::findOrCreate('guest-only-permission', 'web');

        $guestGroup = Group::factory()->asDefaultGuest()->create();
        $guestGroup->givePermissionTo($permission);

        // Create user without the permission
        $user = User::factory()->create();
        $user->groups()->detach();

        // User should not get guest group permission
        $resultWithUser = PermissionService::hasPermissionTo('guest-only-permission', $user);
        // Guest should get the permission
        $resultWithoutUser = PermissionService::hasPermissionTo('guest-only-permission');

        expect($resultWithUser)->toBeFalse();
        expect($resultWithoutUser)->toBeTrue();
    });
});
