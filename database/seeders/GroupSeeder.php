<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        Group::factory()
            ->state(new Sequence(
                ['name' => 'Members', 'is_default_member' => true],
                ['name' => 'Guests', 'is_default_guest' => true],
            ))
            ->count(2)
            ->create();
    }
}
