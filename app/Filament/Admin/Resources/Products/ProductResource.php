<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products;

use App\Enums\FileVisibility;
use App\Enums\InventoryAlertType;
use App\Enums\ProductApprovalStatus;
use App\Enums\ProductTaxCode;
use App\Enums\ProductType;
use App\Filament\Admin\Resources\Products\Actions\AdjustStockAction;
use App\Filament\Admin\Resources\Products\Actions\MarkDamagedAction;
use App\Filament\Admin\Resources\Products\Actions\RestockAction;
use App\Filament\Admin\Resources\Products\Pages\CreateProduct;
use App\Filament\Admin\Resources\Products\Pages\EditProduct;
use App\Filament\Admin\Resources\Products\Pages\ListProducts;
use App\Filament\Admin\Resources\Products\RelationManagers\PricesRelationManager;
use App\Filament\Admin\Resources\Products\RelationManagers\ReviewsRelationManager;
use App\Filament\Admin\Resources\Products\RelationManagers\TransactionsRelationManager;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group as GroupSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Override;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                GroupSchema::make()
                    ->columnSpan(2)
                    ->components([
                        Section::make('Product Information')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                Radio::make('type')
                                    ->live()
                                    ->columnSpanFull()
                                    ->required()
                                    ->options(ProductType::class)
                                    ->default(ProductType::Product->value),
                                TextInput::make('name')
                                    ->maxLength(255)
                                    ->helperText('The product name.')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $context, $state, Set $set): mixed => $context === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->disabledOn('edit')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('A SEO friendly title.')
                                    ->unique(ignoreRecord: true)
                                    ->rules(['alpha_dash']),
                                Select::make('tax_code')
                                    ->required()
                                    ->label('Tax Code')
                                    ->preload()
                                    ->columnSpanFull()
                                    ->searchable()
                                    ->options(ProductTaxCode::class),
                                Select::make('categories')
                                    ->columnSpanFull()
                                    ->preload()
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->required(),
                                Select::make('groups')
                                    ->relationship('groups', 'name')
                                    ->columnSpanFull()
                                    ->preload()
                                    ->multiple()
                                    ->searchable()
                                    ->helperText('Groups that a customer will be assigned when they purchase this product.'),
                                RichEditor::make('description')
                                    ->maxLength(65535)
                                    ->helperText('The main product overview.')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Media')
                            ->columnSpanFull()
                            ->schema([
                                FileUpload::make('featured_image')
                                    ->label('Featured Image')
                                    ->helperText('The main product image.')
                                    ->directory('products')
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
                                    ]),
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
                        Section::make('Files')
                            ->columnSpanFull()
                            ->description('Add files the customer will have access to if they have purchased this product.')
                            ->schema([
                                Repeater::make('files')
                                    ->default([])
                                    ->hiddenLabel()
                                    ->relationship('files')
                                    ->addActionLabel('Add file')
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText('The name of the downloadable file.')
                                            ->label('Name'),
                                        Textarea::make('description')
                                            ->helperText('An optional description of the downloadable file.')
                                            ->maxLength(65535)
                                            ->nullable(),
                                        FileUpload::make('path')
                                            ->directory('files')
                                            ->downloadable()
                                            ->required()
                                            ->visibility('private')
                                            ->hiddenLabel(),
                                        Hidden::make('visibility')
                                            ->default(FileVisibility::Private),
                                    ]),
                            ]),
                        Section::make('Inventory')
                            ->columnSpanFull()
                            ->description('Manage inventory levels and settings for this product.')
                            ->relationship('inventoryItem')
                            ->headerActions([
                                RestockAction::make(),
                                AdjustStockAction::make(),
                                MarkDamagedAction::make(),
                            ])
                            ->columns()
                            ->schema([
                                Grid::make(3)
                                    ->columnSpanFull()
                                    ->visible(fn (Get $get): bool => $get('track_inventory'))
                                    ->schema([
                                        TextEntry::make('quantity_available')
                                            ->color('success')
                                            ->visibleOn('edit')
                                            ->label('Available Quantity')
                                            ->numeric()
                                            ->default(0),
                                        TextEntry::make('quantity_reserved')
                                            ->color('info')
                                            ->visibleOn('edit')
                                            ->label('Reserved')
                                            ->numeric()
                                            ->default(0),
                                        TextEntry::make('quantity_damaged')
                                            ->color('danger')
                                            ->visibleOn('edit')
                                            ->label('Damaged')
                                            ->numeric()
                                            ->default(0),
                                    ]),
                                Toggle::make('track_inventory')
                                    ->live()
                                    ->label('Track Inventory')
                                    ->default(false)
                                    ->helperText('Enable inventory tracking for this product.'),
                                Toggle::make('allow_backorder')
                                    ->label('Allow Backorders')
                                    ->default(false)
                                    ->helperText('Allow orders when out of stock.'),
                                TextInput::make('sku')
                                    ->visible(fn (Get $get): bool => $get('track_inventory'))
                                    ->validationAttribute('SKU')
                                    ->requiredWith('track_inventory')
                                    ->columnSpanFull()
                                    ->label('SKU')
                                    ->maxLength(255),
                                TextInput::make('warehouse_location')
                                    ->visible(fn (Get $get): bool => $get('track_inventory'))
                                    ->columnSpanFull()
                                    ->label('Warehouse Location')
                                    ->maxLength(255),
                                TextInput::make('quantity_available')
                                    ->visible(fn (Get $get, $operation): bool => $get('track_inventory') && $operation === 'create')
                                    ->columnSpanFull()
                                    ->label('Available Quantity')
                                    ->helperText('The currently available quantity. Use the actions above to update.')
                                    ->requiredIf('track_inventory', true)
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('reorder_point')
                                    ->visible(fn (Get $get): bool => $get('track_inventory'))
                                    ->label('Reorder Point')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Trigger low stock alert when quantity falls below this.'),
                                TextInput::make('reorder_quantity')
                                    ->visible(fn (Get $get): bool => $get('track_inventory'))
                                    ->label('Reorder Quantity')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Suggested quantity to order when restocking.'),
                                Repeater::make('inventoryAlert')
                                    ->visible(fn (Get $get, $operation): bool => $get('track_inventory') && $operation === 'edit')
                                    ->columnSpanFull()
                                    ->relationship('alerts')
                                    ->addActionLabel('Add alert')
                                    ->default([])
                                    ->schema([
                                        Select::make('alert_type')
                                            ->label('Alert')
                                            ->options(InventoryAlertType::class)
                                            ->required(),
                                        TextInput::make('threshold_value')
                                            ->helperText('The inventory level at which the alert will be triggered.')
                                            ->label('Threshold Value')
                                            ->required()
                                            ->numeric(),
                                    ]),
                            ]),
                    ]),
                GroupSchema::make()
                    ->components([
                        Section::make('Details')
                            ->visibleOn('edit')
                            ->components([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->since()
                                    ->dateTimeTooltip(),
                                TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->since()
                                    ->dateTimeTooltip(),
                                TextEntry::make('reference_id')
                                    ->label('Reference ID')
                                    ->copyable(),
                                TextEntry::make('url')
                                    ->label('URL')
                                    ->getStateUsing(fn (Product $record): string => route('store.products.show', $record->slug))
                                    ->copyable()
                                    ->suffixAction(fn (Product $record): Action => Action::make('open')
                                        ->url(route('store.products.show', $record->slug), shouldOpenInNewTab: true)
                                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                    ),
                            ]),
                        Section::make('Publishing')
                            ->components([
                                Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->helperText('Mark this product as featured to display it prominently on the store page.')
                                    ->columnSpanFull(),
                                Toggle::make('is_subscription_only')
                                    ->visible(fn (Get $get): bool => $get('type') === ProductType::Subscription)
                                    ->label('Subscription Only')
                                    ->helperText('Only show this product on the subscriptions page - not in the store.')
                                    ->columnSpanFull(),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Allow the product to be purchased.')
                                    ->default(true),
                                Toggle::make('is_visible')
                                    ->label('Visible')
                                    ->helperText('Display the product for purchase. This does not prevent it from being directly accessed.')
                                    ->default(true),
                            ]),
                        Section::make('Purchasing')
                            ->components([
                                Toggle::make('allow_promotion_codes')
                                    ->afterStateUpdated(fn (Set $set): mixed => $set('allow_discount_codes', false))
                                    ->live()
                                    ->label('Allow Promotion Codes')
                                    ->helperText('Allow customers to use promotion codes when purchasing this product that were generated from your payment processor. You may only use promotion codes or discount codes. You may not use both.')
                                    ->columnSpanFull(),
                                Toggle::make('allow_discount_codes')
                                    ->afterStateUpdated(fn (Set $set): mixed => $set('allow_promotion_codes', false))
                                    ->live()
                                    ->label('Allow Discount Codes')
                                    ->default(true)
                                    ->helperText('Allow customers to use discount codes when purchasing this product that were generated from this platform.')
                                    ->columnSpanFull(),
                                TextInput::make('trial_days')
                                    ->required()
                                    ->default(0)
                                    ->visible(fn (Get $get): bool => $get('type') === ProductType::Subscription)
                                    ->label('Trial Mode')
                                    ->suffix('days')
                                    ->helperText('Enable trial mode for this product by providing the number of days the trial is active.')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Compliance')
                            ->components([
                                Select::make('policies')
                                    ->label('Required Policies')
                                    ->helperText('Select policies that customers must agree to when purchasing this product.')
                                    ->columnSpanFull()
                                    ->preload()
                                    ->relationship('policies', 'title', fn (Builder $query) => $query->active()->effective()->orderBy('title'))
                                    ->multiple(),
                            ]),
                        Section::make('Marketplace')
                            ->components([
                                Select::make('seller_id')
                                    ->relationship('seller', 'name')
                                    ->searchable()
                                    ->preload(),
                                Select::make('approval_status')
                                    ->required()
                                    ->label('Approval Status')
                                    ->requiredWith('seller_id')
                                    ->default(ProductApprovalStatus::Approved)
                                    ->options(ProductApprovalStatus::class),
                                TextInput::make('commission_rate')
                                    ->default(0)
                                    ->required()
                                    ->label('Commission Rate')
                                    ->requiredWith('seller_id')
                                    ->rules('lte:1')
                                    ->suffix('%')
                                    ->helperText('The commission rate of the product as a decimal.')
                                    ->numeric(),
                            ]),
                        Section::make('Metadata')
                            ->components([
                                KeyValue::make('metadata.metadata')
                                    ->default(null)
                                    ->helperText('Metadata will be merged with any external payment processor that is used.')
                                    ->hiddenLabel(),
                                Repeater::make('metadata.features')
                                    ->default([])
                                    ->visible(fn (Get $get): bool => $get('type') === ProductType::Subscription)
                                    ->addActionLabel('Add a new feature')
                                    ->simple(TextInput::make('feature')),
                            ]),
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->grow(false)
                    ->alignCenter()
                    ->label('')
                    ->imageSize(60)
                    ->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('seller.name')
                    ->label('Seller')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('approval_status')
                    ->label('Approval Status')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('commission_rate')
                    ->label('Commission Rate')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state): int|float => $state * 100),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('categories.name')
                    ->badge()
                    ->searchable()
                    ->listWithLineBreaks()
                    ->limitList(2),
                TextColumn::make('groups.name')
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('defaultPrice.amount')
                    ->label('Default Price')
                    ->default(0)
                    ->money()
                    ->sortable(),
                IconColumn::make('external_product_id')
                    ->visible(fn () => config('payment.default'))
                    ->label('External Product')
                    ->default(false)
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->default()
                    ->label('Active'),
                SelectFilter::make('approval_status')
                    ->label('Approval Status')
                    ->options(ProductApprovalStatus::class)
                    ->native(false),
                SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                SelectFilter::make('type')
                    ->options(ProductType::class)
                    ->native(false),
                TernaryFilter::make('is_visible')
                    ->default()
                    ->label('Visible'),
                Filter::make('marketplace')
                    ->label('Marketplace Products')
                    ->query(fn (Builder|Product $query): Builder => $query->marketplace()),
                Filter::make('featured')
                    ->label('Featured Products')
                    ->query(fn (Builder|Product $query): Builder => $query->featured()),
                Filter::make('with_external_product_id')
                    ->label('Linked to External Product')
                    ->query(fn (Builder|Product $query): Builder => $query->withExternalProduct()),
                Filter::make('without_external_product_id')
                    ->label('Not Linked to External Product')
                    ->query(fn (Builder|Product $query): Builder => $query->withoutExternalProduct()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order')
            ->reorderable('order');
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            PricesRelationManager::class,
            ReviewsRelationManager::class,
            TransactionsRelationManager::class,
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
        $pendingCount = static::getModel()::where('approval_status', ProductApprovalStatus::Pending)->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
