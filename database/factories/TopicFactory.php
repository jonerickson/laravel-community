<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Forum;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $title = $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'forum_id' => Forum::factory(),
            'created_by' => User::factory(),
            'is_pinned' => $this->faker->boolean(10),
            'is_locked' => $this->faker->boolean(5),
            'views_count' => $this->faker->numberBetween(0, 1000),
            'replies_count' => $this->faker->numberBetween(0, 50),
            'last_reply_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
