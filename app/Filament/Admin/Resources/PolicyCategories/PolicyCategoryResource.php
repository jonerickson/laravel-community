<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyCategories;

use App\Filament\Admin\Resources\PolicyCategories\Pages\CreatePolicyCategory;
use App\Filament\Admin\Resources\PolicyCategories\Pages\EditPolicyCategory;
use App\Filament\Admin\Resources\PolicyCategories\Pages\ListPolicyCategories;
use App\Models\PolicyCategory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Override;

class PolicyCategoryResource extends Resource
{
    protected static ?string $model = PolicyCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $label = 'policy category';

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
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $context, $state, Set $set): mixed => $context === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->disabledOn('edit')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('A SEO friendly title.')
                                    ->unique(ignoreRecord: true)
                                    ->rules(['alpha_dash']),
                                Textarea::make('description')
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
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
                                    ->getStateUsing(fn (PolicyCategory $record): string => route('policies.categories.show', $record->slug))
                                    ->copyable()
                                    ->suffixAction(fn (PolicyCategory $record): Action => Action::make('open')
                                        ->url(route('policies.categories.show', $record->slug), shouldOpenInNewTab: true)
                                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                    ),
                            ]),
                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Allow the category to be accessed.')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no policy categories.')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('policies_count')
                    ->counts('policies')
                    ->label('Policies'),
                IconColumn::make('is_active')
                    ->label('Active')
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
            ->filters([
                TernaryFilter::make('is_active')
                    ->default()
                    ->label('Active'),
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
            ->reorderable('order')
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPolicyCategories::route('/'),
            'create' => CreatePolicyCategory::route('/create'),
            'edit' => EditPolicyCategory::route('/{record}/edit'),
        ];
    }
}
