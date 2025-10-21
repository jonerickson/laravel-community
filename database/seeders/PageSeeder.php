<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        $pages = [
            [
                'title' => 'About Us',
                'description' => 'Learn more about our company and mission',
                'html_content' => '<div class="prose"><h1>About Us</h1><p>Welcome to our platform. We are dedicated to providing the best service possible.</p></div>',
                'show_in_navigation' => true,
                'navigation_label' => 'About',
                'navigation_order' => 10,
            ],
            [
                'title' => 'Contact',
                'description' => 'Get in touch with our team',
                'html_content' => '<div class="prose"><h1>Contact Us</h1><p>We\'d love to hear from you. Reach out to our team anytime.</p></div>',
                'show_in_navigation' => true,
                'navigation_label' => 'Contact',
                'navigation_order' => 20,
            ],
            [
                'title' => 'FAQ',
                'description' => 'Frequently asked questions',
                'html_content' => '<div class="prose"><h1>Frequently Asked Questions</h1><p>Find answers to common questions about our platform.</p></div>',
                'show_in_navigation' => true,
                'navigation_label' => 'FAQ',
                'navigation_order' => 30,
            ],
        ];

        foreach ($pages as $pageData) {
            Page::factory()
                ->published()
                ->create(array_merge($pageData, [
                    'created_by' => $admin?->id ?? User::factory(),
                ]));
        }

        Page::factory()
            ->count(5)
            ->published()
            ->create([
                'created_by' => $admin?->id ?? User::factory(),
            ]);
    }
}
