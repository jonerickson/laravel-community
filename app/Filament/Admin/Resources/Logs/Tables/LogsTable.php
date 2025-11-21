<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Logs\Tables;

use App\Enums\HttpStatusCode;
use App\Models\User;
use App\Models\Webhook;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->searchable(['endpoint', 'request_body', 'request_headers']),
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
                Filter::make('type')
                    ->schema([
                        Select::make('type')
                            ->options([
                                User::class => 'User',
                                Webhook::class => 'Webhook',
                            ]),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['type'],
                            fn (Builder $query, $type): Builder => $query->whereHasMorph('loggable', $type),
                        )),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
