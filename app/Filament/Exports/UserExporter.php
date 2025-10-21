<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('reference_id')
                ->label('Reference ID'),
            ExportColumn::make('name'),
            ExportColumn::make('email'),
            ExportColumn::make('email_verified_at'),
            ExportColumn::make('signature'),
            ExportColumn::make('avatar'),
            ExportColumn::make('stripe_id')
                ->label('Stripe ID'),
            ExportColumn::make('billing_address'),
            ExportColumn::make('billing_address_line_2'),
            ExportColumn::make('billing_city'),
            ExportColumn::make('billing_state'),
            ExportColumn::make('billing_postal_code'),
            ExportColumn::make('billing_country'),
            ExportColumn::make('extra_billing_information'),
            ExportColumn::make('invoice_emails'),
            ExportColumn::make('vat_id')
                ->label('VAT ID'),
            ExportColumn::make('trial_ends_at'),
            ExportColumn::make('onboarded_at'),
            ExportColumn::make('last_seen_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your user export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
