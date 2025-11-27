<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fingerprints;

use App\Filament\Admin\Resources\Fingerprints\Actions\BlacklistAction;
use App\Filament\Admin\Resources\Fingerprints\Actions\UnblacklistAction;
use App\Filament\Admin\Resources\Fingerprints\Pages\ListFingerprints;
use App\Models\Fingerprint;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class FingerprintResource extends Resource
{
    protected static ?string $model = Fingerprint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $label = 'Fingerprints';

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no fingerprints to display.')
            ->columns([
                TextColumn::make('fingerprint_id')
                    ->label('Fingerprint ID')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->fingerprint_id)
                    ->copyable()
                    ->searchable(),
                TextColumn::make('user.name')
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
                    ->color(fn ($state, Fingerprint $record) => match (true) {
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
            ->filters([
                Filter::make('has_user')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('user_id'))
                    ->label('Associated with user'),
                Filter::make('recent_activity')
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
}
