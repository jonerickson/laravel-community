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
        if (SupportTicketCategory::count() === 0) {
            $this->call(SupportTicketCategorySeeder::class);
        }

        SupportTicket::factory()
            ->count(10)
            ->create();
    }
}
