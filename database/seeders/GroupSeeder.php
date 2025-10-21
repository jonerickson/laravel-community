<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        Group::factory()
            ->state(new Sequence(
                ['name' => 'Members', 'description' => 'The default member group that everyone is assigned.', 'is_default_member' => true],
                ['name' => 'Guests', 'description' => 'The group that non-logged in users will assume.', 'is_default_guest' => true],
                ['name' => 'Customers', 'description' => 'All members that have completed at least one successful order.'],
                ['name' => 'Administrators', 'description' => 'The default administrator group.'],
            ))
            ->count(4)
            ->create()
            ->each(function (Group $group) {
                match ($group->name) {
                    'Administrators' => $group->assignRole(Role::Administrator),
                    'Members' => $group->assignRole(Role::User),
                    'Guests' => $group->assignRole(Role::Guest),
                    default => null,
                };
            });
    }
}
