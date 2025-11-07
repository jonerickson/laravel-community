<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FieldType;
use App\Models\Field;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    public function run(): void
    {
        Field::factory()
            ->state([
                'name' => 'Biography',
                'label' => 'Bio',
                'description' => 'Share a bit about yourself...',
                'type' => FieldType::Textarea,
                'is_required' => false,
                'is_public' => true,
            ])->create();

        Field::factory()
            ->state([
                'name' => 'Role',
                'label' => 'Role',
                'description' => 'What brings you here?',
                'type' => FieldType::Select,
                'options' => [
                    ['value' => 'developer', 'label' => 'Developer'],
                    ['value' => 'creator', 'label' => 'Content Creator'],
                    ['value' => 'player', 'label' => 'Player'],
                    ['value' => 'other', 'label' => 'Other'],
                ],
                'is_required' => true,
                'is_public' => true,
            ])->create();
    }
}
