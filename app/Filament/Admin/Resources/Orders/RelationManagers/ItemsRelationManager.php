<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('quantity')
                    ->columnSpanFull()
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
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price.amount')
                    ->money('USD', divideBy: 100)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price.interval')
                    ->label('Interval')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price.interval_count')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove Item')
                    ->requiresConfirmation(),
            ]);
    }
}
