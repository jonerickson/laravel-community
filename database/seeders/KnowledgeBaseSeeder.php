<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\KnowledgeBaseArticleType;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first() ?? User::factory()->create();

        $categories = [
            ['name' => 'Getting Started', 'icon' => 'rocket', 'color' => '#3b82f6', 'order' => 1],
            ['name' => 'Features', 'icon' => 'star', 'color' => '#8b5cf6', 'order' => 2],
            ['name' => 'Troubleshooting', 'icon' => 'wrench', 'color' => '#ef4444', 'order' => 3],
            ['name' => 'API Reference', 'icon' => 'code', 'color' => '#10b981', 'order' => 4],
        ];

        foreach ($categories as $categoryData) {
            $category = KnowledgeBaseCategory::create([
                ...$categoryData,
                'description' => 'Learn more about '.$categoryData['name'],
                'is_active' => true,
            ]);

            KnowledgeBaseArticle::factory()
                ->count(5)
                ->published()
                ->create([
                    'category_id' => $category->id,
                    'created_by' => $admin->id,
                    'type' => match ($category->name) {
                        'Getting Started' => KnowledgeBaseArticleType::Guide,
                        'Features' => KnowledgeBaseArticleType::Guide,
                        'Troubleshooting' => KnowledgeBaseArticleType::Troubleshooting,
                        'API Reference' => KnowledgeBaseArticleType::Guide,
                        default => KnowledgeBaseArticleType::Other,
                    },
                ]);
        }

        KnowledgeBaseArticle::factory()
            ->count(3)
            ->published()
            ->create([
                'created_by' => $admin->id,
                'type' => KnowledgeBaseArticleType::Changelog,
                'category_id' => null,
            ]);

        KnowledgeBaseArticle::factory()
            ->count(3)
            ->published()
            ->create([
                'created_by' => $admin->id,
                'type' => KnowledgeBaseArticleType::Faq,
            ]);
    }
}
