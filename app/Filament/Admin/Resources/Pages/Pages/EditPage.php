<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Pages\Actions\CodeEditorAction;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CodeEditorAction::make()
                ->page(fn (Page $record): \Illuminate\Database\Eloquent\Model|int|string|null => $this->record),
            DeleteAction::make(),
        ];
    }
}
