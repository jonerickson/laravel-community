<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Pages;

use App\Enums\CommissionStatus;
use App\Events\PayoutCreated;
use App\Filament\Admin\Resources\Payouts\PayoutResource;
use App\Models\Commission;
use App\Models\Payout;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Override;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;

    #[Override]
    protected function handleRecordCreation(array $data): Model
    {
        $commissionIds = data_get($data, 'commissions', []);

        unset($data['commissions']);

        $payout = parent::handleRecordCreation($data);

        $commissions = Commission::findMany($commissionIds);
        $commissions->each(fn (Commission $commission) => $commission->update([
            'status' => CommissionStatus::Paid,
            'payout_id' => $payout->getKey(),
        ]));

        /** @var Payout $payout */
        event(new PayoutCreated($payout));

        return $payout;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return ListPayouts::getUrl();
    }
}
