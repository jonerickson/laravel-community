<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumResource\Pages;

use App\Filament\Admin\Resources\ForumResource;
use App\Models\Forum;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForum extends EditRecord
{
    protected static string $resource = ForumResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('view')
                ->url(fn (Forum $record): string => route('forums.show', [$record]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
