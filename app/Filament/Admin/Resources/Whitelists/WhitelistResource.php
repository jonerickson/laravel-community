<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Whitelists;

use App\Filament\Admin\Resources\Whitelists\Pages\CreateWhitelist;
use App\Filament\Admin\Resources\Whitelists\Pages\EditWhitelist;
use App\Filament\Admin\Resources\Whitelists\Pages\ListWhitelists;
use App\Filament\Admin\Resources\Whitelists\Schemas\WhitelistForm;
use App\Filament\Admin\Resources\Whitelists\Tables\WhitelistsTable;
use App\Models\Whitelist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class WhitelistResource extends Resource
{
    protected static ?string $model = Whitelist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $pluralLabel = 'whitelist';

    protected static ?string $label = 'entry';

    protected static ?string $recordTitleAttribute = 'content';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return WhitelistForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return WhitelistsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhitelists::route('/'),
            'create' => CreateWhitelist::route('/create'),
            'edit' => EditWhitelist::route('/{record}/edit'),
        ];
    }
}
