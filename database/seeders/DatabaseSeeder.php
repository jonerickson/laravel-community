<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnnouncementType;
use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Http::preventStrayRequests();

        $this->call([
            PermissionSeeder::class,
            GroupSeeder::class,
        ]);

        Artisan::call('passport:client', [
            '--no-interaction' => true,
            '--personal' => true,
            '--name' => config('app.name'),
        ]);

        $admin = User::factory()->hasAttached(Group::firstOrCreate(['name' => 'Administrators']))->create([
            'name' => 'Test Admin',
            'email' => 'test@deschutesdesigngroup.com',
        ])->assignRole(Role::Administrator);

        User::factory()->create([
            'name' => 'Test Moderator',
            'email' => 'moderator@deschutesdesigngroup.com',
        ])->assignRole(Role::Moderator);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@deschutesdesigngroup.com',
        ])->assignRole(Role::User);

        User::factory()->create([
            'name' => 'Test Support',
            'email' => 'support@deschutesdesigngroup.com',
        ])->assignRole(Role::SupportAgent);

        Announcement::factory()->state([
            'title' => 'Test Announcement',
            'slug' => 'test-announcement',
            'type' => AnnouncementType::Info,
            'content' => 'This is a test announcement.',
        ])->for($admin, 'author')->create();

        $this->call([
            BlogSeeder::class,
            FieldSeeder::class,
            ProductSeeder::class,
            ForumSeeder::class,
            PolicySeeder::class,
            SupportTicketCategorySeeder::class,
            KnowledgeBaseSeeder::class,
            PageSeeder::class,
            WarningSeeder::class,
        ]);
    }
}
