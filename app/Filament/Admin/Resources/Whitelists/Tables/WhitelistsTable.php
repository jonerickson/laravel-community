<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Whitelists\Tables;

use App\Enums\FilterType;
use App\Models\Fingerprint;
use App\Models\User;
use App\Models\Whitelist;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhitelistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No whitelist entries')
            ->columns([
                TextColumn::make('filter')
                    ->sortable()
                    ->badge(),
                TextColumn::make('content')
                    ->placeholder('No Entry')
                    ->label('Entry')
                    ->copyable()
                    ->getStateUsing(fn (Whitelist $record): string => match ($record->filter) {
                        FilterType::Fingerprint => $record->resource instanceof Fingerprint ? $record->resource->fingerprint_id : null,
                        FilterType::User => $record->resource instanceof User ? $record->resource->name : null,
                        default => $record->content,
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->placeholder('No Description')
                    ->wrap()
                    ->limit(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->filters([
                SelectFilter::make('filter')
                    ->options(FilterType::class)
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
