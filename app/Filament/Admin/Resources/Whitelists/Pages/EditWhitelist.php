<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Whitelists\Pages;

use App\Filament\Admin\Resources\Whitelists\WhitelistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Override;

class EditWhitelist extends EditRecord
{
    protected static string $resource = WhitelistResource::class;

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return Str::limit($this->record?->description);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
