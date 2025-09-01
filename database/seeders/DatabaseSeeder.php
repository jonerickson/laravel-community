<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        Announcement::factory()->state([
            'title' => 'Test Announcement',
            'slug' => 'test-announcement',
            'type' => AnnouncementType::Info,
            'content' => 'This is a test announcement.',
        ])->create();

        $group = Group::factory()
            ->count(2)
            ->state(new Sequence(fn ($sequence) => [
                'name' => "Test Group $sequence->index",
            ]))
            ->create();

        User::factory()->hasAttached($group)->create([
            'name' => 'Test User',
            'email' => 'test@deschutesdesigngroup.com',
        ])->assignRole('super_admin');

        $this->call([
            BlogSeeder::class,
            ProductSeeder::class,
            ForumSeeder::class,
            PolicySeeder::class,
            SupportTicketCategorySeeder::class,
        ]);
    }
}
