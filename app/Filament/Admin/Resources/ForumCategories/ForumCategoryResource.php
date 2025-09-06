<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumCategories;

use App\Filament\Admin\Resources\ForumCategories\Pages\CreateForumCategory;
use App\Filament\Admin\Resources\ForumCategories\Pages\EditForumCategory;
use App\Filament\Admin\Resources\ForumCategories\Pages\ListForumCategories;
use App\Filament\Admin\Resources\ForumCategories\Schemas\ForumCategoryForm;
use App\Filament\Admin\Resources\ForumCategories\Tables\ForumCategoriesTable;
use App\Models\ForumCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ForumCategoryResource extends Resource
{
    protected static ?string $model = ForumCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|null|UnitEnum $navigationGroup = 'Forums';

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'category';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ForumCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ForumCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
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
