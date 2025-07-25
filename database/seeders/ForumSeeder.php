<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        $forums = [
            [
                'name' => 'General Discussion',
                'description' => 'Talk about anything and everything',
                'icon' => 'message-square',
                'color' => '#3b82f6',
                'order' => 1,
            ],
            [
                'name' => 'Support',
                'description' => 'Get help with technical issues',
                'icon' => 'help-circle',
                'color' => '#10b981',
                'order' => 2,
            ],
            [
                'name' => 'Feature Requests',
                'description' => 'Suggest new features and improvements',
                'icon' => 'lightbulb',
                'color' => '#f59e0b',
                'order' => 3,
            ],
        ];

        $author = User::first() ?? User::factory();

        Forum::factory()
            ->count(count($forums))
            ->state(new Sequence(...$forums))
            ->create()
            ->each(function (Forum $forum) use ($author) {
                Topic::factory(5)
                    ->for($forum)
                    ->for($author, 'author')
                    ->create()
                    ->each(function (Topic $topic) use ($author) {
                        Post::factory()
                            ->published()
                            ->forum()
                            ->for($topic)
                            ->for($author, 'author')
                            ->count(10)
                            ->create();
                    });
            });
    }
}
