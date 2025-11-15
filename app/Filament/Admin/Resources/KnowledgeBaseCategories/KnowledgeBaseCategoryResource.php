<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeBaseCategories;

use App\Filament\Admin\Resources\KnowledgeBaseCategories\Pages\CreateKnowledgeBaseCategory;
use App\Filament\Admin\Resources\KnowledgeBaseCategories\Pages\EditKnowledgeBaseCategory;
use App\Filament\Admin\Resources\KnowledgeBaseCategories\Pages\ListKnowledgeBaseCategories;
use App\Filament\Admin\Resources\KnowledgeBaseCategories\Schemas\KnowledgeBaseCategoryForm;
use App\Filament\Admin\Resources\KnowledgeBaseCategories\Tables\KnowledgeBaseCategoriesTable;
use App\Models\KnowledgeBaseCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class KnowledgeBaseCategoryResource extends Resource
{
    protected static ?string $model = KnowledgeBaseCategory::class;

    protected static ?string $label = 'category';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|null|UnitEnum $navigationGroup = 'Knowledge Base';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?int $navigationSort = -3;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return KnowledgeBaseCategoryForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return KnowledgeBaseCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeBaseCategories::route('/'),
            'create' => CreateKnowledgeBaseCategory::route('/create'),
            'edit' => EditKnowledgeBaseCategory::route('/{record}/edit'),
        ];
    }
}
