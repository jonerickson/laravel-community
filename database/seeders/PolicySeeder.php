<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Policy;
use App\Models\PolicyCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $category = PolicyCategory::factory()->state([
            'name' => 'Policy Category 1',
            'is_active' => true,
        ])->create();

        $author = User::first() ?? User::factory();

        Policy::factory()
            ->recycle($category)
            ->count(10)
            ->for($author, 'author')
            ->state(new Sequence(fn (Sequence $sequence) => [
                'title' => "Policy $sequence->index",
                'is_active' => true,
            ]))
            ->create();
    }
}
