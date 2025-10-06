<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\WarningConsequenceType;
use App\Models\Warning;
use App\Models\WarningConsequence;
use Illuminate\Database\Seeder;

class WarningSeeder extends Seeder
{
    public function run(): void
    {
        $consequences = [
            [
                'type' => WarningConsequenceType::ModerateContent,
                'threshold' => 10,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'type' => WarningConsequenceType::PostRestriction,
                'threshold' => 25,
                'duration_days' => 60,
                'is_active' => true,
            ],
            [
                'type' => WarningConsequenceType::Ban,
                'threshold' => 50,
                'duration_days' => 365,
                'is_active' => true,
            ],
        ];

        foreach ($consequences as $consequence) {
            WarningConsequence::query()->updateOrCreate(
                ['type' => $consequence['type']],
                $consequence
            );
        }

        $warnings = [
            [
                'name' => 'Spam',
                'description' => 'Posting irrelevant, repetitive, or promotional content',
                'points' => 5,
                'days_applied' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Harassment',
                'description' => 'Targeted harassment or bullying of other users',
                'points' => 15,
                'days_applied' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Inappropriate Content',
                'description' => 'Posting content that violates community guidelines',
                'points' => 10,
                'days_applied' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Abuse',
                'description' => 'Abusive behavior towards other community members',
                'points' => 20,
                'days_applied' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Impersonation',
                'description' => 'Pretending to be another user or entity',
                'points' => 25,
                'days_applied' => 180,
                'is_active' => true,
            ],
            [
                'name' => 'False Information',
                'description' => 'Deliberately spreading misinformation or false information',
                'points' => 15,
                'days_applied' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Other Violation',
                'description' => 'Other community guideline violations',
                'points' => 5,
                'days_applied' => 30,
                'is_active' => true,
            ],
        ];

        foreach ($warnings as $warning) {
            Warning::query()->updateOrCreate(
                ['name' => $warning['name']],
                $warning
            );
        }
    }
}
