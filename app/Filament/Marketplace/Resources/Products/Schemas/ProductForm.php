<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->helperText('The product name.')
                    ->maxLength(255)
                    ->required(),
                Select::make('categories')
                    ->columnSpanFull()
                    ->preload()
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->required(),
                RichEditor::make('description')
                    ->helperText('The main product overview.')
                    ->maxLength(65535)
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('featured_image')
                    ->directory('products/featured-images')
                    ->visibility('public')
                    ->helperText('The main product image.')
                    ->label('Featured Image')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                    ]),
            ]);
    }
}
