<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Policies\Pages;

use App\Filament\Admin\Resources\Policies\PolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Override;

class EditPolicy extends EditRecord
{
    protected static string $resource = PolicyResource::class;

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
