<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ActivityLogs;

use App\Filament\Admin\Resources\ActivityLogs\Pages\ListActivityLogs;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Override;
use Phiki\Grammar\Grammar;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Logs';

    protected static ?string $label = 'log';

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->emptyStateDescription('There are no logs to view right now.')
            ->columns([
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Str::title($state))
                    ->color(fn (string $state): string => match ($state) {
                        'user' => 'info',
                        'auth' => 'danger',
                        'blog' => 'success',
                        'forum' => 'warning',
                        'store' => 'primary',
                        'marketplace' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->default('System')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_string($state) ? Str::title($state) : $state)
                    ->default(new HtmlString('&ndash;'))
                    ->color(fn (?string $state): string => match ($state) {
                        'created', 'login', 'email_verified' => 'success',
                        'updated', 'password_reset' => 'warning',
                        'deleted', 'failed_login' => 'danger',
                        'logout' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Log Type')
                    ->options([
                        'user' => 'User',
                        'auth' => 'Authentication',
                        'blog' => 'Blog',
                        'forum' => 'Forum',
                        'store' => 'Store',
                        'marketplace' => 'Marketplace',
                    ]),
                SelectFilter::make('event')
                    ->label('Event Type')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'failed_login' => 'Failed Login',
                        'password_reset' => 'Password Reset',
                        'email_verified' => 'Email Verified',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('view_details')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->modalHeading('Log Properties')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalDescription('View the JSON properties and metadata for this log entry.')
                    ->slideOver()
                    ->schema([
                        Section::make('Details')
                            ->schema([
                                TextEntry::make('description')
                                    ->label('Description'),
                                TextEntry::make('log_name')
                                    ->label('Log Type')
                                    ->formatStateUsing(fn (string $state): string => Str::title($state)),
                                TextEntry::make('event')
                                    ->label('Event'),
                                TextEntry::make('causer.name')
                                    ->label('User')
                                    ->default('System'),
                                TextEntry::make('subject_type')
                                    ->label('Subject Type')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? class_basename($state) : 'N/A'),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                            ])
                            ->columns(2),

                        CodeEntry::make('properties')
                            ->label('Properties')
                            ->grammar(Grammar::Json)
                            ->copyable()
                            ->visible(fn (Activity $record): bool => ! empty($record->changes))
                            ->getStateUsing(function (Activity $record): string {
                                if (empty($record->properties)) {
                                    return '{}';
                                }

                                return $record->properties->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            }),

                        CodeEntry::make('changes')
                            ->label('Changes')
                            ->grammar(Grammar::Json)
                            ->copyable()
                            ->visible(fn (Activity $record): bool => ! empty($record->changes))
                            ->getStateUsing(function (Activity $record): string {
                                if (empty($record->properties)) {
                                    return '{}';
                                }

                                return $record->properties->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            }),

                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
