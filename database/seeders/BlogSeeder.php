<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::first() ?? User::factory();

        Post::factory()
            ->count(5)
            ->for($author, 'author')
            ->published()
            ->blog()
            ->state(new Sequence(
                fn (Sequence $sequence) => [
                    'featured_image' => "boilerplate/blog-$sequence->index.jpg",
                ],
            ))
            ->create()
            ->each(fn (Post $post) => Comment::factory()->count(3)->for($post, 'commentable')->create());
    }
}
