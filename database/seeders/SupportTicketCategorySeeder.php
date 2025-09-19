<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SupportTicketCategory;
use Illuminate\Database\Seeder;

class SupportTicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        SupportTicketCategory::factory()->createMany([
            [
                'name' => 'Technical Support',
                'description' => 'Issues related to technical problems, bugs, or system errors',
                'color' => '#3B82F6',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Billing',
                'description' => 'Questions about billing, payments, and subscriptions',
                'color' => '#10B981',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'General Inquiry',
                'description' => 'General questions and information requests',
                'color' => '#6B7280',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Feature Request',
                'description' => 'Suggestions for new features or improvements',
                'color' => '#8B5CF6',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Bug Report',
                'description' => 'Reports of bugs or unexpected behavior',
                'color' => '#EF4444',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Account Issues',
                'description' => 'Problems with user accounts, login, or permissions',
                'color' => '#F59E0B',
                'order' => 6,
                'is_active' => true,
            ],
        ]);
    }
}
