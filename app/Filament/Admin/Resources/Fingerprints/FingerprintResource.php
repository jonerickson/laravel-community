<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fingerprints;

use App\Filament\Admin\Resources\Fingerprints\Actions\BlacklistAction;
use App\Filament\Admin\Resources\Fingerprints\Actions\UnblacklistAction;
use App\Filament\Admin\Resources\Fingerprints\Pages\ListFingerprints;
use App\Filament\Admin\Resources\Users\RelationManagers\FingerprintsRelationManager;
use App\Models\Fingerprint;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class FingerprintResource extends Resource
{
    protected static ?string $model = Fingerprint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $label = 'Fingerprints';

    protected static ?string $recordTitleAttribute = 'fingerprint_id';

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no fingerprints to display.')
            ->description(sprintf('Current Blacklist Threshold: >= %d', config('services.fingerprint.suspect_score_threshold')))
            ->columns([
                TextColumn::make('fingerprint_id')
                    ->label('Fingerprint ID')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->fingerprint_id)
                    ->copyable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->hiddenOn(FingerprintsRelationManager::class)
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Guest'),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('suspect_score')
                    ->label('Suspect Score')
                    ->sortable()
                    ->color(fn ($state, Fingerprint $record): string => match (true) {
                        $state >= 25, $record->is_blacklisted => 'danger',
                        $state >= 11 && $state < 25 => 'warning',
                        default => 'success'
                    })
                    ->badge(),
                TextColumn::make('first_seen_at')
                    ->placeholder('Not Seen')
                    ->label('First Seen')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_seen_at')
                    ->placeholder('Not Seen')
                    ->label('Last Seen')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_seen_at')
                    ->placeholder('Not Checked')
                    ->label('Last Checked')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->user_agent),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filtersFormColumns(2)
            ->filters([
                SelectFilter::make('user.name')
                    ->columnSpanFull()
                    ->relationship('user', 'name')
                    ->preload()
                    ->multiple()
                    ->searchable(),
                Filter::make('last_checked_at')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        DatePicker::make('last_checked_at_from')
                            ->label('Last Checked At After'),
                        DatePicker::make('last_checked_at_until')
                            ->label('Last Checked At Before'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['last_checked_at_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('last_checked_at', '>=', $date),
                        )
                        ->when(
                            $data['last_checked_at_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('last_checked_at', '<=', $date),
                        )),
                Filter::make('last_seen_at')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        DatePicker::make('last_seen_at_from')
                            ->label('Last Seen At After'),
                        DatePicker::make('last_seen_at_until')
                            ->label('Last Seen At Before'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['last_seen_at_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('last_seen_at', '>=', $date),
                        )
                        ->when(
                            $data['last_seen_at_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('last_seen_at', '<=', $date),
                        )),
                Filter::make('suspect_score')
                    ->label('Suspect Score Between')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('suspect_score_from')
                            ->numeric()
                            ->label('Suspect Score Greater Than'),
                        TextInput::make('suspect_score_until')
                            ->numeric()
                            ->label('Suspect Score Less Than'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['suspect_score_from'],
                            fn (Builder $query, $score): Builder => $query->where('suspect_score', '>=', $score),
                        )
                        ->when(
                            $data['suspect_score_until'],
                            fn (Builder $query, $score): Builder => $query->where('suspect_score', '<=', $score),
                        )),
                Filter::make('has_user')
                    ->columnSpanFull()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('user_id'))
                    ->label('Associated with user'),
                Filter::make('recent_activity')
                    ->columnSpanFull()
                    ->query(fn (Builder $query): Builder => $query->where('last_seen_at', '>=', now()->subDays(7)))
                    ->label('Active in last 7 days'),
            ])
            ->recordActions([
                BlacklistAction::make(),
                UnblacklistAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_seen_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFingerprints::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['fingerprint_id', 'request_id'];
    }

    public static function getGlobalSearchResultDetails(Fingerprint|Model $record): array
    {
        return [
            'Request ID' => $record->request_id,
            'User' => $record->user?->name,
            'IP Address' => $record->ip_address,
        ];
    }
}
