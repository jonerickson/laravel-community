<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Webhooks\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Phiki\Grammar\Grammar;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('endpoint')
                    ->columnSpanFull(),
                TextEntry::make('method')
                    ->badge(),
                TextEntry::make('status')
                    ->placeholder('Unknown')
                    ->badge(),
                KeyValueEntry::make('request_headers')
                    ->label('Headers')
                    ->keyLabel('Header')
                    ->placeholder('No headers')
                    ->columnSpanFull(),
                CodeEntry::make('request_body')
                    ->copyable()
                    ->label('Body')
                    ->grammar(Grammar::Json)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('endpoint')
            ->description('The logs for this resource.')
            ->columns([
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
                ViewAction::make()
                    ->slideOver(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
