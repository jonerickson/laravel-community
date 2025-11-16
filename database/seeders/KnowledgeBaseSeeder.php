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
        $author = User::first() ?? User::factory();

        $categories = [
            ['name' => 'Getting Started', 'icon' => 'rocket', 'color' => '#3b82f6'],
            ['name' => 'Features', 'icon' => 'star', 'color' => '#8b5cf6'],
            ['name' => 'Troubleshooting', 'icon' => 'wrench', 'color' => '#ef4444'],
            ['name' => 'API Reference', 'icon' => 'code', 'color' => '#10b981'],
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
                ->for($author, 'author')
                ->for($category, 'category')
                ->create([
                    'type' => match ($category->name) {
                        'Getting Started' => KnowledgeBaseArticleType::Guide,
                        'Features' => KnowledgeBaseArticleType::Guide,
                        'Troubleshooting' => KnowledgeBaseArticleType::Troubleshooting,
                        'API Reference' => KnowledgeBaseArticleType::Guide,
                        default => KnowledgeBaseArticleType::Other,
                    },
                ]);
        }
    }
}
