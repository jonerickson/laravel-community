<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => \App\Enums\Role::Administrator->value]);
        $role->givePermissionTo(Permission::all());

        Role::firstOrCreate(['name' => \App\Enums\Role::Guest->value]);
        Role::firstOrCreate(['name' => \App\Enums\Role::Moderator->value]);
        Role::firstOrCreate(['name' => \App\Enums\Role::User->value]);
        Role::firstOrCreate(['name' => \App\Enums\Role::SupportAgent->value]);
    }
}
