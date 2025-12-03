<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Pages\Actions\CodeEditorAction;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Override;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return Str::limit($this->record?->description);
    }

    protected function getHeaderActions(): array
    {
        return [
            CodeEditorAction::make()
                ->page(fn (Page $record): Model|int|string|null => $this->record),
            ViewAction::make()
                ->url(fn (Page $record) => $record->url, shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
