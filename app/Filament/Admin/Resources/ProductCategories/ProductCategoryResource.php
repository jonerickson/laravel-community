<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ProductCategories;

use App\Filament\Admin\Resources\ProductCategories\Pages\CreateProductCategory;
use App\Filament\Admin\Resources\ProductCategories\Pages\EditProductCategory;
use App\Filament\Admin\Resources\ProductCategories\Pages\ListProductCategories;
use App\Models\ProductCategory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Override;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static ?string $label = 'product category';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Category Information')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                TextInput::make('name')
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
                                Select::make('parent_id')
                                    ->label('Parent Category')
                                    ->relationship('parent', 'name')
                                    ->columnSpanFull()
                                    ->nullable()
                                    ->preload()
                                    ->searchable()
                                    ->helperText('Optional parent category to create a subcategory.'),
                                Textarea::make('description')
                                    ->helperText('A helpful description on what the product category features.')
                                    ->columnSpanFull()
                                    ->maxLength(65535)
                                    ->rows(3),
                            ]),
                        Section::make('Media')
                            ->columnSpanFull()
                            ->schema([
                                FileUpload::make('featured_image')
                                    ->label('Featured Image')
                                    ->helperText('Add a category image to be displayed on the store index.')
                                    ->directory('products/categories')
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
                            ]),
                    ]),
                Group::make()
                    ->schema([
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
                                TextEntry::make('url')
                                    ->label('URL')
                                    ->getStateUsing(fn (ProductCategory $record): string => route('store.categories.show', $record->slug))
                                    ->copyable()
                                    ->suffixAction(fn (ProductCategory $record): Action => Action::make('open')
                                        ->url(route('store.categories.show', $record->slug), shouldOpenInNewTab: true)
                                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                    ),
                            ]),
                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Allow the category to be accessed.')
                                    ->default(true),
                                Toggle::make('is_visible')
                                    ->label('Visible')
                                    ->helperText('Display the category in the store. This does not prevent it from being directly accessed.')
                                    ->default(true),
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
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('No Parent')
                    ->sortable()
                    ->searchable()
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->sortable(),
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->default()
                    ->label('Active'),
                TernaryFilter::make('is_visible')
                    ->default()
                    ->label('Visible'),
            ])
            ->reorderable('order')
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductCategories::route('/'),
            'create' => CreateProductCategory::route('/create'),
            'edit' => EditProductCategory::route('/{record}/edit'),
        ];
    }
}
