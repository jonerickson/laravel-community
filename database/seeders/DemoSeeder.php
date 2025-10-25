<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Http::preventStrayRequests();

        if (Permission::count() === 0) {
            $this->call(PermissionSeeder::class);
        }

        if (Group::count() === 0) {
            $this->call(GroupSeeder::class);
        }

        $this->call([
            BlogSeeder::class,
            ProductSeeder::class,
            ForumSeeder::class,
            PolicySeeder::class,
            SupportTicketCategorySeeder::class,
            PageSeeder::class,
            WarningSeeder::class,
        ]);
    }
}
