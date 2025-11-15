<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeBaseArticles;

use App\Filament\Admin\Resources\KnowledgeBaseArticles\Pages\CreateKnowledgeBaseArticle;
use App\Filament\Admin\Resources\KnowledgeBaseArticles\Pages\EditKnowledgeBaseArticle;
use App\Filament\Admin\Resources\KnowledgeBaseArticles\Pages\ListKnowledgeBaseArticles;
use App\Filament\Admin\Resources\KnowledgeBaseArticles\Schemas\KnowledgeBaseArticleForm;
use App\Filament\Admin\Resources\KnowledgeBaseArticles\Tables\KnowledgeBaseArticlesTable;
use App\Models\KnowledgeBaseArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class KnowledgeBaseArticleResource extends Resource
{
    protected static ?string $model = KnowledgeBaseArticle::class;

    protected static ?string $label = 'article';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|null|UnitEnum $navigationGroup = 'Knowledge Base';

    protected static ?string $navigationLabel = 'Articles';

    protected static ?int $navigationSort = -3;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return KnowledgeBaseArticleForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return KnowledgeBaseArticlesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeBaseArticles::route('/'),
            'create' => CreateKnowledgeBaseArticle::route('/create'),
            'edit' => EditKnowledgeBaseArticle::route('/{record}/edit'),
        ];
    }
}
