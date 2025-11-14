<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets;

use App\Filament\Admin\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Filament\Admin\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Admin\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Admin\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Filament\Admin\Resources\SupportTickets\Schemas\SupportTicketInfolist;
use App\Filament\Admin\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Override;
use UnitEnum;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?string $navigationLabel = 'Tickets';

    protected static string|UnitEnum|null $navigationGroup = 'Support';

    protected static ?int $navigationSort = -2;

    protected static ?string $recordTitleAttribute = 'ticket_number';

    public static function getNavigationBadge(): ?string
    {
        return Number::format(static::getModel()::active()->count());
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return SupportTicketForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return SupportTicketInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'view' => ViewSupportTicket::route('/{record}'),
            'edit' => EditSupportTicket::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
