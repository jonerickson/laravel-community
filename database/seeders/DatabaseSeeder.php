<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
        ]);

        Announcement::factory()->state([
            'title' => 'Test Announcement',
            'slug' => 'test-announcement',
            'type' => AnnouncementType::Info,
            'content' => 'This is a test announcement.',
        ])->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@deschutesdesigngroup.com',
        ])->assignRole(Utils::getSuperAdminName());

        $this->call([
            BlogSeeder::class,
            ProductSeeder::class,
            ForumSeeder::class,
        ]);
    }
}
