<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make()
                    ->contained(false)
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Details')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->schema([
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
                                    ->columnSpanFull(),
                                FileUpload::make('featured_image')
                                    ->directory('products')
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
                            ]),
                        Tabs\Tab::make('Gallery')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->schema([
                                Repeater::make('images')
                                    ->label('Gallery Images')
                                    ->helperText('Additional product images shown in the gallery.')
                                    ->relationship('images')
                                    ->default([])
                                    ->addActionLabel('Add image')
                                    ->schema([
                                        FileUpload::make('path')
                                            ->label('Image')
                                            ->directory('products/gallery')
                                            ->visibility('public')
                                            ->downloadable()
                                            ->previewable()
                                            ->openable()
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                            ])
                                            ->required(),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
