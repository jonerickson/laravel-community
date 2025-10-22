<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blacklists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BlacklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No blacklist entries')
            ->columns([
                TextColumn::make('content')
                    ->searchable(),
                TextColumn::make('description')
                    ->limit(),
                IconColumn::make('is_regex')
                    ->sortable()
                    ->label('Regex')
                    ->boolean(),
                TextColumn::make('warning.name')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->filters([
                TernaryFilter::make('is_regex')
                    ->label('Regex'),
                SelectFilter::make('warning.name')
                    ->relationship('warning', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
