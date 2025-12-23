<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders;

use App\Filament\Admin\Resources\Notes\RelationManagers\NotesRelationManager;
use App\Filament\Admin\Resources\Orders\Pages\CreateOrder;
use App\Filament\Admin\Resources\Orders\Pages\EditOrder;
use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Orders\RelationManagers\CommissionRelationManager;
use App\Filament\Admin\Resources\Orders\RelationManagers\DiscountsRelationManager;
use App\Filament\Admin\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\Orders\Schemas\OrderForm;
use App\Filament\Admin\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Admin\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Override;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $recordTitleAttribute = 'reference_id';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::make(),
            DiscountsRelationManager::make(),
            NotesRelationManager::make(),
            CommissionRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference_id'];
    }

    public static function getGlobalSearchResultDetails(Order|Model $record): array
    {
        return [
            'Customer' => $record->user->name,
            'Due' => Number::currency($record->amount_due ?? 0),
            'Status' => $record->status?->getLabel(),
            'Items' => $record->items->map->name->implode(', '),
        ];
    }
}
