<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Policies;

use App\Filament\Admin\Resources\Policies\Pages\CreatePolicy;
use App\Filament\Admin\Resources\Policies\Pages\EditPolicy;
use App\Filament\Admin\Resources\Policies\Pages\ListPolicies;
use App\Models\Policy;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Policies';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Policy Information')
                            ->columns(2)
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $context, $state, Set $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('policy_category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('version')
                                    ->nullable()
                                    ->maxLength(255),
                                RichEditor::make('content')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_active')
                                    ->helperText('Enable the policy for viewing.')
                                    ->required()
                                    ->default(true),
                                DateTimePicker::make('effective_at')
                                    ->default(today())
                                    ->label('Effective Date')
                                    ->helperText('Leave empty for immediate effect'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no policies avaialble.')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('version')
                    ->searchable(),
                TextColumn::make('effective_at')
                    ->label('Effective')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Effective immediately'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('author.name')
                    ->label('Created By')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
            ->reorderable('order')
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPolicies::route('/'),
            'create' => CreatePolicy::route('/create'),
            'edit' => EditPolicy::route('/{record}/edit'),
        ];
    }
}
