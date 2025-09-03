<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products;

use App\Enums\ProductType;
use App\Filament\Admin\Resources\Products\Pages\CreateProduct;
use App\Filament\Admin\Resources\Products\Pages\EditProduct;
use App\Filament\Admin\Resources\Products\Pages\ListProducts;
use App\Filament\Admin\Resources\Products\RelationManagers\PricesRelationManager;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Information')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        Radio::make('type')
                            ->columnSpanFull()
                            ->required()
                            ->options(ProductType::class)
                            ->default(ProductType::Product->value),
                        Toggle::make('is_featured')
                            ->label('Featured Product')
                            ->helperText('Mark this product as featured to display it prominently on the store page.')
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->helperText('The product name.')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($operation, $state, Set $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->helperText('A SEO friendly title.')
                            ->required(),
                        Select::make('categories')
                            ->columnSpanFull()
                            ->preload()
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->required(),
                        Select::make('policies')
                            ->label('Required Policies')
                            ->helperText('Select policies that customers must agree to when purchasing this product.')
                            ->columnSpanFull()
                            ->preload()
                            ->relationship('policies', 'title', fn (Builder $query) => $query->active()->effective()->orderBy('title'))
                            ->multiple(),
                        Select::make('groups')
                            ->relationship('groups', 'name')
                            ->columnSpanFull()
                            ->preload()
                            ->multiple()
                            ->searchable()
                            ->helperText('Groups that a customer will be assigned when they purchase this product.'),
                        RichEditor::make('description')
                            ->helperText('The main product overview.')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Media')
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('featured_image')
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
                Section::make('Files')
                    ->columnSpanFull()
                    ->description('Add files the customer will have access to if they have purchased this product.')
                    ->schema([
                        Repeater::make('files')
                            ->hiddenLabel()
                            ->relationship('files')
                            ->addActionLabel('Add file')
                            ->schema([
                                FileUpload::make('files')
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('categories.name')
                    ->badge()
                    ->searchable()
                    ->listWithLineBreaks()
                    ->limitList(2),
                TextColumn::make('policies.title')
                    ->label('Required Policies')
                    ->badge()
                    ->searchable()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->toggleable(),
                TextColumn::make('defaultPrice.amount')
                    ->label('Default Price')
                    ->default(0)
                    ->money()
                    ->sortable(),
                TextColumn::make('external_product_id')
                    ->label('External Product ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Stripe Product ID copied')
                    ->placeholder('Not linked')
                    ->icon(fn ($state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->iconColor(fn ($state): string => $state ? 'success' : 'danger')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ProductType::class)
                    ->native(false),
                Filter::make('products')
                    ->label('Products Only')
                    ->query(fn (Builder $query): Builder => $query->products()),
                Filter::make('subscriptions')
                    ->label('Subscriptions Only')
                    ->query(fn (Builder $query): Builder => $query->subscriptions()),
                Filter::make('with_stripe')
                    ->label('Linked to Stripe')
                    ->query(fn (Builder $query): Builder => $query->withStripeProduct()),
                Filter::make('without_stripe')
                    ->label('Not Linked to Stripe')
                    ->query(fn (Builder $query): Builder => $query->withoutStripeProduct()),
                Filter::make('featured')
                    ->label('Featured Products')
                    ->query(fn (Builder $query): Builder => $query->featured()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PricesRelationManager::class,
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
