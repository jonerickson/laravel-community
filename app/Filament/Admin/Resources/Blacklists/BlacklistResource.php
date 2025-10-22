<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blacklists;

use App\Filament\Admin\Resources\Blacklists\Pages\CreateBlacklist;
use App\Filament\Admin\Resources\Blacklists\Pages\EditBlacklist;
use App\Filament\Admin\Resources\Blacklists\Pages\ListBlacklists;
use App\Filament\Admin\Resources\Blacklists\Schemas\BlacklistForm;
use App\Filament\Admin\Resources\Blacklists\Tables\BlacklistsTable;
use App\Models\Blacklist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class BlacklistResource extends Resource
{
    protected static ?string $model = Blacklist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedXCircle;

    protected static ?string $pluralLabel = 'blacklist';

    protected static ?string $label = 'entry';

    protected static ?string $recordTitleAttribute = 'content';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return BlacklistForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return BlacklistsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlacklists::route('/'),
            'create' => CreateBlacklist::route('/create'),
            'edit' => EditBlacklist::route('/{record}/edit'),
        ];
    }
}
