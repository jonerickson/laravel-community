<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\ProductType;
use App\Filament\Admin\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Admin\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Admin\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Admin\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                        Forms\Components\Radio::make('type')
                            ->columnSpanFull()
                            ->required()
                            ->options(ProductType::class)
                            ->default(ProductType::Product->value),
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
                        Forms\Components\TextInput::make('stripe_product_id')
                            ->label('Stripe Product ID')
                            ->helperText('The Stripe product ID for payment processing (e.g., prod_xxxxxxxxxxxx).')
                            ->placeholder('prod_xxxxxxxxxxxx')
                            ->columnSpanFull(),
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
                    ]),
                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image')
                            ->disk('public')
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
                    ]),
                Forms\Components\Section::make('Files')
                    ->description('Add files the customer will have access to if they have purchased this product.')
                    ->schema([
                        Forms\Components\Repeater::make('files')
                            ->hiddenLabel()
                            ->relationship('files')
                            ->addActionLabel('Add file')
                            ->schema([
                                Forms\Components\FileUpload::make('files')
                                    ->visibility('private')
                                    ->helperText('Files the customer will have access to after purchasing the product.')
                                    ->label('Downloads')
                                    ->multiple(),
                            ]),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('categories.name')
                    ->badge()
                    ->searchable()
                    ->listWithLineBreaks()
                    ->limitList(2),
                Tables\Columns\TextColumn::make('defaultPrice.amount')
                    ->label('Default Price')
                    ->default(0)
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stripe_product_id')
                    ->label('Stripe ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Stripe Product ID copied')
                    ->placeholder('Not linked')
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->iconColor(fn ($state) => $state ? 'success' : 'danger')
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('type')
                    ->options(ProductType::class)
                    ->native(false),
                Tables\Filters\Filter::make('products')
                    ->label('Products Only')
                    ->query(fn (Builder $query): Builder => $query->products()),
                Tables\Filters\Filter::make('subscriptions')
                    ->label('Subscriptions Only')
                    ->query(fn (Builder $query): Builder => $query->subscriptions()),
                Tables\Filters\Filter::make('with_stripe')
                    ->label('Linked to Stripe')
                    ->query(fn (Builder $query): Builder => $query->withStripeProduct()),
                Tables\Filters\Filter::make('without_stripe')
                    ->label('Not Linked to Stripe')
                    ->query(fn (Builder $query): Builder => $query->withoutStripeProduct()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PricesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getNavigationBadge();

        return match (true) {
            $count > 20 => 'warning',
            $count > 0 => 'success',
            default => 'gray',
        };
    }
}
