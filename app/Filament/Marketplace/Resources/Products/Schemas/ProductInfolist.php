<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Resources\Products\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Product Information')
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('slug'),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('tax_code')
                            ->label('Tax Code')
                            ->badge(),
                        TextEntry::make('categories.name')
                            ->badge()
                            ->listWithLineBreaks(),
                        TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Section::make('Media')
                    ->columnSpan(2)
                    ->schema([
                        ImageEntry::make('featured_image')
                            ->label('Featured Image'),
                    ]),
            ]);
    }
}
