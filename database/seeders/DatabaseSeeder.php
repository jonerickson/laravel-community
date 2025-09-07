<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;
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

        Announcement::factory()->state([
            'title' => 'Test Announcement',
            'slug' => 'test-announcement',
            'type' => AnnouncementType::Info,
            'content' => 'This is a test announcement.',
        ])->create();

        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'test@deschutesdesigngroup.com',
        ])->assignRole('super-admin');

        User::factory()->create([
            'name' => 'Test Moderator',
            'email' => 'moderator@deschutesdesigngroup.com',
        ])->assignRole('moderator');

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@deschutesdesigngroup.com',
        ])->assignRole('user');

        $this->call([
            BlogSeeder::class,
            ProductSeeder::class,
            ForumSeeder::class,
            PolicySeeder::class,
            SupportTicketCategorySeeder::class,
        ]);
    }
}
