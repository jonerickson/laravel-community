<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes;

use App\Enums\Role;
use App\Filament\Admin\Resources\Disputes\Pages\ListDisputes;
use App\Filament\Admin\Resources\Disputes\Pages\ViewDispute;
use App\Filament\Admin\Resources\Disputes\Schemas\DisputeInfolist;
use App\Filament\Admin\Resources\Disputes\Tables\DisputesTable;
use App\Models\Dispute;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Override;

class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $recordTitleAttribute = 'external_dispute_id';

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(Role::Administrator);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return DisputeInfolist::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return DisputesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisputes::route('/'),
            'view' => ViewDispute::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['external_dispute_id'];
    }
}
