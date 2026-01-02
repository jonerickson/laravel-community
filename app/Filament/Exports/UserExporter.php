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
            ExportColumn::make('email_verified_at')
                ->label('Email Verified At'),
            ExportColumn::make('signature'),
            ExportColumn::make('avatar'),
            ExportColumn::make('stripe_id')
                ->label('Stripe ID'),
            ExportColumn::make('payouts_enabled')
                ->label('Payouts Enabled'),
            ExportColumn::make('external_payout_account_id')
                ->label('External Payout Account ID'),
            ExportColumn::make('external_payout_account_onboarded_at')
                ->label('External Payout Account Onboarded At'),
            ExportColumn::make('external_payout_account_capabilities')
                ->label('External Payout Account Capabilities'),
            ExportColumn::make('pm_type')
                ->label('Payment Method Type'),
            ExportColumn::make('pm_last_four')
                ->label('Payment Method Last Four'),
            ExportColumn::make('pm_expiration')
                ->label('Payment Method Expiration'),
            ExportColumn::make('billing_address')
                ->label('Billing Address'),
            ExportColumn::make('billing_address_line_2')
                ->label('Billing Address Line 2'),
            ExportColumn::make('billing_city')
                ->label('Billing City'),
            ExportColumn::make('billing_state')
                ->label('Billing State'),
            ExportColumn::make('billing_postal_code')
                ->label('Billing Postal Code'),
            ExportColumn::make('billing_country')
                ->label('Billing Country'),
            ExportColumn::make('extra_billing_information')
                ->label('Billing Information'),
            ExportColumn::make('invoice_emails')
                ->label('Invoice Emails'),
            ExportColumn::make('vat_id')
                ->label('VAT ID'),
            ExportColumn::make('trial_ends_at')
                ->label('Trial Ends At'),
            ExportColumn::make('onboarded_at')
                ->label('Onboarded At'),
            ExportColumn::make('last_seen_at')
                ->label('Last Seen At'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
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
