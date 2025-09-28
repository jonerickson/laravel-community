<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Forums\Pages;

use App\Filament\Admin\Resources\Forums\ForumResource;
use App\Models\Forum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

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
            DeleteAction::make(),
            Action::make('view')
                ->url(fn (Forum $record): string => route('forums.show', [$record]))
                ->openUrlInNewTab(),
        ];
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
