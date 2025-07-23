<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::first() ?? User::factory();

        Post::factory()
            ->count(50)
            ->for($author, 'author')
            ->create()
            ->each(fn (Post $post) => Comment::factory()->count(3)->for($post, 'commentable')->create());
    }
}
