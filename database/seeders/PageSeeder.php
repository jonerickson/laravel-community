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
                'is_published' => false,
                'description' => 'Learn more about our company and mission',
                'show_in_navigation' => true,
                'navigation_label' => 'About',
                'navigation_order' => 10,
                'html_content' => <<<'HTML'
<div class="flex flex-col gap-1">
  <div class="font-bold text-xl">About Us</div>
  <p class="font-light">Welcome to our platform. We are dedicated to providing the best service possible.</p>
</div>
HTML
            ],
            [
                'title' => 'FAQ',
                'is_published' => false,
                'description' => 'Frequently asked questions',
                'show_in_navigation' => true,
                'navigation_label' => 'FAQ',
                'navigation_order' => 11,
                'html_content' => <<<'HTML'
<div class="flex flex-col gap-1">
  <div class="font-bold text-xl">Frequently Asked Questions</div>
  <p class="font-light">Find answers to common questions about our platform.</p>
</div>
HTML
            ],
            [
                'title' => 'Contact',
                'is_published' => true,
                'published_at' => now(),
                'description' => 'Get in touch with our team',
                'show_in_navigation' => true,
                'navigation_label' => 'Contact',
                'navigation_order' => 12,
                'html_content' => <<<'HTML'
<div class="flex flex-col gap-6">
  <div class="flex flex-col gap-1">
    <div class="font-bold text-xl">Contact Us</div>
    <p class="font-light">Learn how to get a hold of us below.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="flex flex-col gap-2 p-4 rounded-lg border border-border shadow-sm bg-background relative">
      <div class="font-semibold text-lg text-primary">Phone</div>
      <p class="font-light text-foreground">+1 (555) 123-4567</p>
      <p class="text-sm text-muted-foreground">Mon-Fri 9am-5pm EST</p>
    </div>

    <div class="flex flex-col gap-2 p-4 rounded-lg border border-border shadow-sm bg-background relative">
      <div class="font-semibold text-lg">Email</div>
      <p class="font-light text-foreground">support@example.com</p>
      <p class="text-sm text-muted-foreground">We'll respond within 24 hours</p>
    </div>

    <div class="flex flex-col gap-2 p-4 rounded-lg border border-border shadow-sm bg-background relative">
      <div class="font-semibold text-lg">Address</div>
      <p class="font-light text-foreground">123 Business Street<br>Suite 100<br>City, State 12345</p>
    </div>
  </div>
</div>
HTML
            ],
        ];

        foreach ($pages as $pageData) {
            Page::factory()
                ->create(array_merge($pageData, [
                    'created_by' => $admin?->id ?? User::factory(),
                ]));
        }

        Page::factory()
            ->count(5)
            ->unpublished()
            ->create([
                'created_by' => $admin?->id ?? User::factory(),
            ]);
    }
}
