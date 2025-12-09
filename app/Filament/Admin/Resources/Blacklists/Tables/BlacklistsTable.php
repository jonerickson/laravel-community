<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blacklists\Tables;

use App\Enums\FilterType;
use App\Models\Blacklist;
use App\Models\Fingerprint;
use App\Models\User;
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
                TextColumn::make('filter')
                    ->sortable()
                    ->badge(),
                TextColumn::make('content')
                    ->placeholder('No Entry')
                    ->label('Entry')
                    ->copyable()
                    ->getStateUsing(fn (Blacklist $record): ?string => match ($record->filter) {
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
                IconColumn::make('is_regex')
                    ->sortable()
                    ->label('Regex')
                    ->boolean(),
                TextColumn::make('warning.name')
                    ->placeholder('No Warning')
                    ->badge(),
                TextColumn::make('author.name')
                    ->placeholder('System')
                    ->searchable()
                    ->sortable(),
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
            ])
            ->defaultSort('created_at', 'desc');
    }
}
