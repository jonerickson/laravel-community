<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\AnnouncementType;
use App\Filament\Admin\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use App\Filament\Admin\Resources\AnnouncementResource\Pages\EditAnnouncement;
use App\Filament\Admin\Resources\AnnouncementResource\Pages\ListAnnouncements;
use App\Filament\Admin\Resources\AnnouncementResource\Pages\ViewAnnouncement;
use App\Models\Announcement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Announcement Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, Forms\Set $set) => $context === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null
                            ),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash']),
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options(AnnouncementType::class)
                            ->default(AnnouncementType::Info->value)
                            ->native(false),
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active announcements will be displayed to users.'),
                        Forms\Components\Toggle::make('is_dismissible')
                            ->label('Dismissible')
                            ->default(true)
                            ->helperText('Allow users to dismiss this announcement.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date & Time')
                            ->helperText('Leave empty to display immediately.')
                            ->native(false),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('End Date & Time')
                            ->helperText('Leave empty to display indefinitely.')
                            ->after('starts_at')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Author')
                    ->schema([
                        Forms\Components\Select::make('created_by')
                            ->relationship('author', 'name')
                            ->required()
                            ->default(\Illuminate\Support\Facades\Auth::id())
                            ->preload()
                            ->searchable(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no announcements.')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_dismissible')
                    ->label('Dismissible')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Immediately'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->searchable(),
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
                    ->options(AnnouncementType::class),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_dismissible')
                    ->label('Dismissible'),
                Tables\Filters\Filter::make('current')
                    ->label('Currently Active')
                    ->query(fn (Builder $query): Builder => $query->current()),
                Tables\Filters\Filter::make('scheduled')
                    ->label('Scheduled')
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '>', now())
                    ),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query->where('ends_at', '<', now())
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'view' => ViewAnnouncement::route('/{record}'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::current()->count();
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
