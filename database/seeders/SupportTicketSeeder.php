<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use Illuminate\Database\Seeder;

class SupportTicketSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have categories and users
        if (SupportTicketCategory::count() === 0) {
            $this->call(SupportTicketCategorySeeder::class);
        }

        // Create sample tickets
        SupportTicket::factory()
            ->count(20)
            ->new()
            ->create();

        SupportTicket::factory()
            ->count(15)
            ->open()
            ->create();

        SupportTicket::factory()
            ->count(10)
            ->inProgress()
            ->assigned()
            ->create();

        SupportTicket::factory()
            ->count(8)
            ->resolved()
            ->create();

        SupportTicket::factory()
            ->count(5)
            ->closed()
            ->create();

        // Create some high priority tickets
        SupportTicket::factory()
            ->count(3)
            ->highPriority()
            ->open()
            ->create();

        // Create critical tickets
        SupportTicket::factory()
            ->count(2)
            ->critical()
            ->new()
            ->create();

        // Create some external tickets
        SupportTicket::factory()
            ->count(5)
            ->external('zendesk')
            ->create();
    }
}
