<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
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
                Select::make('price_id')
                    ->relationship('price', 'name')
                    ->label('Product')
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => Price::query()
                        ->active()
                        ->with('product')
                        ->whereRelation('product', 'name', 'like', sprintf('%%%s%%', $search))
                        ->whereHas('product', fn (Builder|Product $query) => $query->active())
                        ->orWhere('name', 'like', sprintf('%%%s%%', $search))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Price $price): array => [$price->id => sprintf('%s: %s', $price->product->getLabel(), $price->getLabel())])
                        ->toArray())
                    ->options(fn (Get $get) => Price::query()
                        ->active()
                        ->with('product')
                        ->whereHas('product', fn (Builder|Product $query) => $query->active())
                        ->get()
                        ->mapWithKeys(fn (Price $price): array => [$price->id => sprintf('%s: %s', $price->product->getLabel(), $price->getLabel())]))
                    ->preload()
                    ->searchable(['prices.name', 'products.name']),
                TextInput::make('quantity')
                    ->default(1)
                    ->required()
                    ->numeric(),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->helperText('An optional line item description.')
                    ->nullable(),
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->default(new HtmlString('&ndash;'))
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
                    ->money()
                    ->sortable(),
                TextColumn::make('external_item_id')
                    ->label('External Item ID')
                    ->copyable()
                    ->searchable()
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
                EditAction::make()
                    ->modalDescription("Update the order's line item."),
                DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove Item')
                    ->modalDescription('Are you sure you want to remove this item from the order?')
                    ->modalSubmitActionLabel('Remove')
                    ->requiresConfirmation(),
            ]);
    }
}
