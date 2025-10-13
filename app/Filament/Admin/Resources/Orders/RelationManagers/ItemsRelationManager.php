<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Override;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedListBullet;

    protected static ?string $badgeColor = 'info';

    #[Override]
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Order $ownerRecord */
        return (string) $ownerRecord->items->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('product_id')
                    ->required()
                    ->relationship('product', 'name')
                    ->preload()
                    ->live(onBlur: true)
                    ->searchable(),
                Select::make('price_id')
                    ->label('Price')
                    ->disableOptionWhen(fn (Get $get): bool => blank($get('product_id')))
                    ->required()
                    ->options(fn (Get $get) => Price::query()->where('product_id', $get('product_id'))->pluck('name', 'id'))
                    ->preload()
                    ->searchable(),
                TextInput::make('quantity')
                    ->default(1)
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Order Items')
            ->description('The products belonging to the order.')
            ->columns([
                TextColumn::make('name')
                    ->default(new HtmlString('&ndash;'))
                    ->formatStateUsing(fn (OrderItem $item): string => $item->getLabel())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->default(new HtmlString('&ndash;'))
                    ->formatStateUsing(fn ($state): string|Htmlable|null => $state instanceof Price ? $state->getLabel() : $state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Total')
                    ->money('USD', divideBy: 100)
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add order item')
                    ->modalHeading('Add Order Item')
                    ->modalDescription('Add a new item to the order.')
                    ->modalSubmitActionLabel('Add')
                    ->createAnother(false),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove Item')
                    ->modalDescription('Remove an item from the order.')
                    ->modalSubmitActionLabel('Remove')
                    ->requiresConfirmation(),
            ]);
    }
}
