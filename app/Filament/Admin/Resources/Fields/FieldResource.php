<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fields;

use App\Filament\Admin\Resources\Fields\Pages\CreateField;
use App\Filament\Admin\Resources\Fields\Pages\EditField;
use App\Filament\Admin\Resources\Fields\Pages\ListFields;
use App\Filament\Admin\Resources\Fields\Schemas\FieldForm;
use App\Filament\Admin\Resources\Fields\Tables\FieldsTable;
use App\Models\Field;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class FieldResource extends Resource
{
    protected static ?string $model = Field::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return FieldForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return FieldsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFields::route('/'),
            'create' => CreateField::route('/create'),
            'edit' => EditField::route('/{record}/edit'),
        ];
    }
}
