<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserFingerprints;

use App\Filament\Admin\Resources\UserFingerprints\Pages\ListUserFingerprints;
use App\Models\Fingerprint;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class UserFingerprintResource extends Resource
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
                IconColumn::make('is_banned')
                    ->label('Banned')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->is_banned ? $record->ban_reason : null),
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
                TernaryFilter::make('is_banned')
                    ->label('Ban Status')
                    ->trueLabel('Banned devices only')
                    ->falseLabel('Active devices only')
                    ->native(false),
                Filter::make('has_user')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('user_id'))
                    ->label('Associated with user'),
                Filter::make('recent_activity')
                    ->query(fn (Builder $query): Builder => $query->where('last_seen_at', '>=', now()->subDays(7)))
                    ->label('Active in last 7 days'),
            ])
            ->recordActions([
                Action::make('ban')
                    ->label('Ban Device')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Fingerprint $record): bool => ! $record->is_banned)
                    ->schema([
                        Textarea::make('ban_reason')
                            ->label('Ban Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (Fingerprint $record, array $data): void {
                        $record->banFingerprint($data['ban_reason'], Filament::auth()->user());
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Ban Device')
                    ->modalDescription('Are you sure you want to ban this device? All users accessing from this device will be blocked.')
                    ->modalSubmitActionLabel('Ban Device'),
                Action::make('unban')
                    ->label('Unban Device')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Fingerprint $record): bool => $record->is_banned)
                    ->action(fn (Fingerprint $record) => $record->unbanFingerprint())
                    ->requiresConfirmation()
                    ->modalHeading('Unban Device')
                    ->modalDescription('Are you sure you want to unban this device?')
                    ->modalSubmitActionLabel('Unban Device'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulk_ban')
                        ->label('Ban Selected Devices')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Textarea::make('ban_reason')
                                ->label('Ban Reason')
                                ->required()
                                ->maxLength(1000),
                        ])
                        ->action(function (array $data, $records): void {
                            foreach ($records as $record) {
                                if (! $record->is_banned) {
                                    $record->banFingerprint($data['ban_reason'], Filament::auth()->user());
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ban Selected Devices')
                        ->modalDescription('Are you sure you want to ban the selected devices?'),
                ]),
            ])
            ->defaultSort('last_seen_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserFingerprints::route('/'),
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
