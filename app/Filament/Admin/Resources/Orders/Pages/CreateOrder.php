<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('edit', $this->getRedirectUrlParameters());
    }
}
