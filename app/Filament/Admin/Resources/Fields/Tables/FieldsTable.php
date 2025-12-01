<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fields\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns;
use Filament\Tables\Table;

class FieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                Columns\IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
                Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->label('Public'),
                Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order')
            ->defaultSort('order', 'desc');
    }
}
