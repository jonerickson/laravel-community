<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumCategories;

use App\Filament\Admin\Resources\ForumCategories\Pages\CreateForumCategory;
use App\Filament\Admin\Resources\ForumCategories\Pages\EditForumCategory;
use App\Filament\Admin\Resources\ForumCategories\Pages\ListForumCategories;
use App\Filament\Admin\Resources\ForumCategories\RelationManagers\GroupsRelationManager;
use App\Filament\Admin\Resources\ForumCategories\Schemas\ForumCategoryForm;
use App\Filament\Admin\Resources\ForumCategories\Tables\ForumCategoriesTable;
use App\Models\ForumCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class ForumCategoryResource extends Resource
{
    protected static ?string $model = ForumCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $label = 'forum category';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ForumCategoryForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ForumCategoriesTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            GroupsRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForumCategories::route('/'),
            'create' => CreateForumCategory::route('/create'),
            'edit' => EditForumCategory::route('/{record}/edit'),
        ];
    }
}
