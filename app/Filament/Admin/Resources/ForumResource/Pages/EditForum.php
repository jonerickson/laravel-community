<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumResource\Pages;

use App\Filament\Admin\Resources\ForumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForum extends EditRecord
{
    protected static string $resource = ForumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('view')
                ->label('View Forum')
                ->icon('heroicon-o-eye')
                ->url(fn (): string => "/forums/{$this->record->slug}")
                ->openUrlInNewTab(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
