<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

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

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@deschutesdesigngroup.com',
        ])->assignRole(Utils::getSuperAdminName());

        $category = ProductCategory::factory()->create();

        Product::factory()
            ->count(5)
            ->recycle($category)
            ->hasAttached($category, relationship: 'categories')
            ->create();

        Post::factory()
            ->count(5)
            ->for($user, 'author')
            ->create()
            ->each(fn (Post $post) => Comment::factory()->count(3)->for($post, 'commentable')->create());
    }
}
