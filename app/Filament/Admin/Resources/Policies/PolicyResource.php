<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Policies;

use App\Filament\Admin\Resources\Policies\Pages\CreatePolicy;
use App\Filament\Admin\Resources\Policies\Pages\EditPolicy;
use App\Filament\Admin\Resources\Policies\Pages\ListPolicies;
use App\Models\Policy;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Override;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    #[Override]
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
                                    ->afterStateUpdated(fn (string $context, $state, Set $set): mixed => $context === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->disabledOn('edit')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('A SEO friendly title.')
                                    ->unique(ignoreRecord: true)
                                    ->rules(['alpha_dash']),
                                Select::make('policy_category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('version')
                                    ->nullable()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->columnSpanFull()
                                    ->nullable()
                                    ->maxLength(65535),
                                RichEditor::make('content')
                                    ->required()
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
                                    ->getStateUsing(fn (Policy $record): string => route('policies.show', ['category' => $record->category->slug, 'policy' => $record->slug]))
                                    ->copyable()
                                    ->suffixAction(fn (Policy $record): Action => Action::make('open')
                                        ->url(route('policies.show', ['category' => $record->category->slug, 'policy' => $record->slug]), shouldOpenInNewTab: true)
                                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                    ),
                            ]),
                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Enable the policy for viewing.')
                                    ->default(true),
                                DateTimePicker::make('effective_at')
                                    ->default(today())
                                    ->label('Effective Date')
                                    ->helperText('Leave empty for immediate effect'),
                            ]),
                        Section::make('Author')
                            ->columnSpanFull()
                            ->collapsed()
                            ->schema([
                                Select::make('created_by')
                                    ->relationship('author', 'name')
                                    ->required()
                                    ->default(Auth::id())
                                    ->preload()
                                    ->searchable(),
                            ]),
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no policies available.')
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
                    ->placeholder('Effective Immediately'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Created By')
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
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
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
