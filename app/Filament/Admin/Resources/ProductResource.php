<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Admin\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Admin\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Store';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->helperText('The product name.')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($operation, $state, Forms\Set $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->helperText('A SEO friendly title.')
                            ->required(),
                        Forms\Components\Select::make('categories')
                            ->columnSpanFull()
                            ->preload()
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->required(),
                        Forms\Components\RichEditor::make('description')
                            ->helperText('The main product overview.')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Tax will be automatically calculated.')
                            ->numeric()
                            ->stripCharacters(',')
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('$')
                            ->suffix('USD'),
                    ]),
                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image')
                            ->visibility('public')
                            ->helperText('The main product image.')
                            ->label('Featured Image')
                            ->image(),
                    ]),
                Forms\Components\Section::make('Files')
                    ->schema([
                        Forms\Components\FileUpload::make('files')
                            ->visibility('private')
                            ->helperText('Files the customer will have access to after purchasing the product.')
                            ->label('Downloads')
                            ->multiple(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('categories.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
