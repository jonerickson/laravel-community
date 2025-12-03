<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Pages;

use App\Filament\Admin\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Override;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return Str::limit($this->record?->excerpt);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->url(fn (Post $record) => $record->url, shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
