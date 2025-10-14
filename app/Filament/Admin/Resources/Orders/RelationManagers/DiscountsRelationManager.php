<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Models\Order;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Override;

class DiscountsRelationManager extends RelationManager
{
    protected static string $relationship = 'discounts';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedTicket;

    protected static ?string $badgeColor = 'success';

    #[Override]
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Order $ownerRecord */
        return (string) $ownerRecord->discounts->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Discounts Applied')
            ->description('The discount codes applied to this order.')
            ->columns([
                TextColumn::make('code')
                    ->default(new HtmlString('&ndash;'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('pivot.amount_applied')
                    ->label('Amount Applied')
                    ->money()
                    ->sortable(),
                TextColumn::make('pivot.balance_before')
                    ->label('Balance Before')
                    ->money()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('pivot.balance_after')
                    ->label('Balance After')
                    ->money()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('pivot.created_at')
                    ->label('Applied At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ]);
    }
}
