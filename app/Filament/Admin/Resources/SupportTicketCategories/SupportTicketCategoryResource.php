<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTicketCategories;

use App\Filament\Admin\Resources\SupportTicketCategories\Pages\CreateSupportTicketCategory;
use App\Filament\Admin\Resources\SupportTicketCategories\Pages\EditSupportTicketCategory;
use App\Filament\Admin\Resources\SupportTicketCategories\Pages\ListSupportTicketCategories;
use App\Filament\Admin\Resources\SupportTicketCategories\Schemas\SupportTicketCategoryForm;
use App\Filament\Admin\Resources\SupportTicketCategories\Tables\SupportTicketCategoriesTable;
use App\Models\SupportTicketCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class SupportTicketCategoryResource extends Resource
{
    protected static ?string $model = SupportTicketCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Categories';

    protected static string|UnitEnum|null $navigationGroup = 'Support';

    protected static ?int $navigationSort = -2;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return SupportTicketCategoryForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return SupportTicketCategoriesTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTicketCategories::route('/'),
            'create' => CreateSupportTicketCategory::route('/create'),
            'edit' => EditSupportTicketCategory::route('/{record}/edit'),
        ];
    }
}
