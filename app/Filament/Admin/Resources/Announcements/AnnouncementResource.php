<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Announcements;

use App\Enums\AnnouncementType;
use App\Filament\Admin\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Admin\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Admin\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Override;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Announcement Details')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set): mixed => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->disabledOn('edit')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('A SEO friendly title.')
                                    ->unique(ignoreRecord: true)
                                    ->rules(['alpha_dash']),
                                Select::make('type')
                                    ->columnSpanFull()
                                    ->required()
                                    ->options(AnnouncementType::class)
                                    ->default(AnnouncementType::Info->value)
                                    ->native(false),
                                RichEditor::make('content')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Schedule')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                DateTimePicker::make('starts_at')
                                    ->label('Start Date & Time')
                                    ->helperText('Leave empty to display immediately.')
                                    ->native(false),
                                DateTimePicker::make('ends_at')
                                    ->label('End Date & Time')
                                    ->helperText('Leave empty to display indefinitely.')
                                    ->after('starts_at')
                                    ->native(false),
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
                            ]),
                        Section::make('Settings')
                            ->columnSpanFull()
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Only active announcements will be displayed to users.'),
                                Toggle::make('is_dismissible')
                                    ->label('Dismissible')
                                    ->default(true)
                                    ->helperText('Allow users to dismiss this announcement.'),
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
            ->emptyStateDescription('There are no announcements to display.')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->badge(),
                IconColumn::make('is_active')
                    ->sortable()
                    ->boolean()
                    ->label('Active'),
                IconColumn::make('is_dismissible')
                    ->boolean()
                    ->sortable()
                    ->label('Dismissible'),
                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Immediately'),
                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->searchable(),
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
                TernaryFilter::make('is_dismissible')
                    ->label('Dismissible'),
                SelectFilter::make('type')
                    ->multiple()
                    ->searchable()
                    ->options(AnnouncementType::class),
                Filter::make('current')
                    ->label('Currently Active')
                    ->query(fn (Builder $query): Builder => $query->current()),
                Filter::make('scheduled')
                    ->label('Scheduled')
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '>', now())
                    ),
                Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query->where('ends_at', '<', now())
                    ),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Number::format(static::getModel()::current()->count());
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getNavigationBadge();

        return match (true) {
            $count > 5 => 'warning',
            $count > 0 => 'success',
            default => 'gray',
        };
    }
}
