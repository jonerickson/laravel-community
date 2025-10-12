<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Warnings;

use App\Filament\Admin\Resources\Warnings\Pages\CreateWarning;
use App\Filament\Admin\Resources\Warnings\Pages\EditWarning;
use App\Filament\Admin\Resources\Warnings\Pages\ListWarnings;
use App\Filament\Admin\Resources\Warnings\Schemas\WarningForm;
use App\Filament\Admin\Resources\Warnings\Tables\WarningsTable;
use App\Models\Warning;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Override;

class WarningResource extends Resource
{
    protected static ?string $model = Warning::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return WarningForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return WarningsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarnings::route('/'),
            'create' => CreateWarning::route('/create'),
            'edit' => EditWarning::route('/{record}/edit'),
        ];
    }
}
