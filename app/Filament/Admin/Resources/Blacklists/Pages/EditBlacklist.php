<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blacklists\Pages;

use App\Filament\Admin\Resources\Blacklists\BlacklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Override;

class EditBlacklist extends EditRecord
{
    protected static string $resource = BlacklistResource::class;

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
