<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Community',
                'description' => 'Talk about anything and everything',
                'icon' => 'message-square',
                'color' => '#3b82f6',
                'featured_image' => 'boilerplate/forum-category-1.jpeg',
                'forums' => [
                    [
                        'name' => 'General Discussion',
                        'description' => 'Chat about anything related to our community and services.',
                    ],
                    [
                        'name' => 'Feedback',
                        'description' => 'Share your thoughts and suggestions to help us improve.',
                    ],
                    [
                        'name' => 'Off Topic',
                        'description' => 'Casual conversations about life, hobbies, and everything else.',
                    ],
                ],
            ],
            [
                'name' => 'Help and Technical Support',
                'description' => 'Get help with technical issues',
                'icon' => 'help-circle',
                'color' => '#10b981',
                'forums' => [
                    [
                        'name' => 'General Questions',
                        'description' => 'Ask questions and get help from our community members.',
                    ],
                    [
                        'name' => 'Technical Problems',
                        'description' => 'Troubleshoot technical issues and find solutions.',
                    ],
                    [
                        'name' => 'Design and Customization',
                        'description' => 'Get help with styling, themes, and visual customizations.',
                    ],
                ],
            ],
        ];

        $author = User::first() ?? User::factory()->create();
        $memberGroup = Group::defaultMemberGroup() ?? Group::factory()->asDefaultMemberGroup()->create();
        $guestGroup = Group::defaultGuestGroup() ?? Group::factory()->asDefaultGuest()->create();
        $adminGroup = Group::query()->where('name', 'Administrators')->first() ?? Group::factory()->state(['name' => 'Administrators'])->create();

        foreach ($categories as $category) {
            $forumCategory = ForumCategory::factory()
                ->state(Arr::except($category, ['forums']))
                ->hasAttached($memberGroup)
                ->hasAttached($guestGroup)
                ->create();

            foreach ($category['forums'] ?? [] as $forum) {
                Forum::factory()
                    ->state($forum)
                    ->for($forumCategory, 'category')
                    ->hasAttached($memberGroup)
                    ->hasAttached($guestGroup)
                    ->create()
                    ->each(function (Forum $forum) use ($author) {
                        Topic::factory(3)
                            ->for($forum)
                            ->for($author, 'author')
                            ->create()
                            ->each(function (Topic $topic) use ($author) {
                                Post::factory()
                                    ->published()
                                    ->forum()
                                    ->for($topic)
                                    ->for($author, 'author')
                                    ->count(3)
                                    ->create();
                            });
                    });
            }
        }

        $internalForumCategory = ForumCategory::factory()->state([
            'name' => 'Internal Only',
        ])->hasAttached($adminGroup)->create();

        $internalForums = ['Administrators', 'Moderators'];

        foreach ($internalForums as $internalForumName) {
            $parentForum = Forum::factory()
                ->for($internalForumCategory, 'category')
                ->state([
                    'name' => $internalForumName,
                    'description' => "Private discussions for $internalForumName.",
                ])
                ->hasAttached($adminGroup)
                ->create();

            Forum::factory()
                ->for($internalForumCategory, 'category')
                ->state([
                    'name' => 'General Discussion',
                    'parent_id' => $parentForum->id,
                ])
                ->hasAttached($adminGroup)
                ->create();
        }
    }
}
