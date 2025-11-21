<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Webhooks\RelationManagers;

use App\Enums\HttpStatusCode;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Phiki\Grammar\Grammar;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Request')
                    ->contained(false)
                    ->schema([
                        TextEntry::make('endpoint')
                            ->columnSpanFull(),
                        TextEntry::make('method')
                            ->badge(),
                        KeyValueEntry::make('request_headers')
                            ->label('Headers')
                            ->keyLabel('Header')
                            ->placeholder('No headers')
                            ->columnSpanFull(),
                        CodeEntry::make('request_body')
                            ->placeholder('No body')
                            ->copyable()
                            ->label('Body')
                            ->grammar(Grammar::Json)
                            ->columnSpanFull(),
                    ]),
                Section::make('Response')
                    ->contained(false)
                    ->schema([
                        TextEntry::make('status')
                            ->placeholder('Unknown')
                            ->badge(),
                        KeyValueEntry::make('response_headers')
                            ->label('Headers')
                            ->keyLabel('Header')
                            ->placeholder('No headers')
                            ->columnSpanFull(),
                        CodeEntry::make('response_content')
                            ->placeholder('No content')
                            ->copyable()
                            ->label('Content')
                            ->grammar(Grammar::Json)
                            ->columnSpanFull(),
                    ]),
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
            ->filters([
                SelectFilter::make('status')
                    ->options(HttpStatusCode::class)
                    ->preload()
                    ->multiple()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
