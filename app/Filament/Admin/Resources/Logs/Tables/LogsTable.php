<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Logs\Tables;

use App\Enums\HttpStatusCode;
use App\Models\User;
use App\Models\Webhook;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('endpoint')
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('request_id')
                    ->label('Request ID')
                    ->sortable()
                    ->copyable()
                    ->searchable(),
                TextColumn::make('endpoint')
                    ->sortable()
                    ->copyable()
                    ->searchable(),
                TextColumn::make('method')
                    ->sortable()
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->placeholder('Unknown')
                    ->sortable()
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(HttpStatusCode::class)
                    ->preload()
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('loggable_type')
                    ->label('Type')
                    ->options([
                        User::class => 'User',
                        Webhook::class => 'Webhook',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
