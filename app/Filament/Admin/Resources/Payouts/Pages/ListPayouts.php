<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Pages;

use App\Filament\Admin\Resources\Payouts\PayoutResource;
use App\Filament\Admin\Resources\Payouts\Widgets\PayoutOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    protected ?string $subheading = 'Record and initiate payouts to your marketplace sellers.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            PayoutOverview::make(),
        ];
    }
}
