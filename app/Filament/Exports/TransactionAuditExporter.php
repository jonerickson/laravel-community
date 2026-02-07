<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Order;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class TransactionAuditExporter extends Exporter
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference_id')
                ->label('Reference ID'),
            ExportColumn::make('external_payment_id')
                ->label('Stripe Payment ID'),
            ExportColumn::make('user.email')
                ->label('User Email'),
            ExportColumn::make('amount_paid')
                ->label('Amount Paid')
                ->formatStateUsing(fn (?int $state): string => $state ? Number::currency($state / 100) : '$0.00'),
            ExportColumn::make('status')
                ->label('Status'),
            ExportColumn::make('latest_consent_at')
                ->label('Latest Consent At')
                ->state(fn (Order $record): ?string => $record->user->policyConsents
                    ->sortByDesc('consented_at')
                    ->first()
                    ?->consented_at
                    ?->toDateTimeString()),
            ExportColumn::make('consent_ip_address')
                ->label('Consent IP Address')
                ->state(fn (Order $record): ?string => $record->user->policyConsents
                    ->sortByDesc('consented_at')
                    ->first()
                    ?->ip_address),
            ExportColumn::make('consent_policy_version')
                ->label('Consent Policy Version')
                ->state(fn (Order $record): ?string => $record->user->policyConsents
                    ->sortByDesc('consented_at')
                    ->first()
                    ?->policy
                    ?->version),
            ExportColumn::make('created_at')
                ->label('Created At'),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user.policyConsents.policy']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction audit export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
