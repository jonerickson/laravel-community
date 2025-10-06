<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WarningConsequences;

use App\Filament\Admin\Resources\WarningConsequences\Pages\CreateWarningConsequence;
use App\Filament\Admin\Resources\WarningConsequences\Pages\EditWarningConsequence;
use App\Filament\Admin\Resources\WarningConsequences\Pages\ListWarningConsequences;
use App\Filament\Admin\Resources\WarningConsequences\Schemas\WarningConsequenceForm;
use App\Filament\Admin\Resources\WarningConsequences\Tables\WarningConsequencesTable;
use App\Models\WarningConsequence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class WarningConsequenceResource extends Resource
{
    protected static ?string $model = WarningConsequence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static bool $shouldRegisterNavigation = false;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return WarningConsequenceForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return WarningConsequencesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarningConsequences::route('/'),
            'create' => CreateWarningConsequence::route('/create'),
            'edit' => EditWarningConsequence::route('/{record}/edit'),
        ];
    }
}
