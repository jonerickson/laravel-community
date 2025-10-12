<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Override;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('reference_id')
                ->requiredMapping()
                ->rules(['required', 'max:36']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->rules(['email', 'max:255']),
            ImportColumn::make('email_verified_at')
                ->rules(['email', 'datetime']),
            ImportColumn::make('signature'),
            ImportColumn::make('password')
                ->rules(['max:255']),
            ImportColumn::make('app_authentication_secret'),
            ImportColumn::make('app_authentication_recovery_codes'),
            ImportColumn::make('has_email_authentication')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'email', 'boolean']),
            ImportColumn::make('avatar')
                ->rules(['max:255']),
            ImportColumn::make('stripe_id')
                ->rules(['max:255']),
            ImportColumn::make('pm_type')
                ->rules(['max:255']),
            ImportColumn::make('pm_last_four')
                ->rules(['max:4']),
            ImportColumn::make('pm_expiration')
                ->rules(['max:255']),
            ImportColumn::make('extra_billing_information'),
            ImportColumn::make('trial_ends_at')
                ->rules(['datetime']),
            ImportColumn::make('billing_address')
                ->rules(['max:255']),
            ImportColumn::make('billing_address_line_2')
                ->rules(['max:255']),
            ImportColumn::make('billing_city')
                ->rules(['max:255']),
            ImportColumn::make('billing_state')
                ->rules(['max:255']),
            ImportColumn::make('billing_postal_code')
                ->rules(['max:25']),
            ImportColumn::make('vat_id')
                ->rules(['max:50']),
            ImportColumn::make('invoice_emails')
                ->rules(['email']),
            ImportColumn::make('billing_country')
                ->rules(['max:2']),
            ImportColumn::make('last_seen_at')
                ->rules(['datetime']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your user import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if (($failedRowsCount = $import->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }

    #[Override]
    public function resolveRecord(): User
    {
        return User::firstOrNew([
            'email' => $this->data['email'],
        ]);
    }
}
