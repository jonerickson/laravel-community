<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Logs\Schemas;

use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Phiki\Grammar\Grammar;

class LogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Request')
                    ->schema([
                        TextEntry::make('endpoint')
                            ->columnSpanFull(),
                        TextEntry::make('method')
                            ->badge(),
                        KeyValueEntry::make('request_headers')
                            ->helperText('Sensitive headers will be removed.')
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
}
