<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Actions;

use App\Models\Post;
use Filament\Actions\Action;
use Override;

class PublishAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-check');
        $this->color('info');
        $this->requiresConfirmation();
        $this->visible(fn (Post $record): bool => ! $record->is_published);
        $this->successNotificationTitle('The post has been successfully published.');
        $this->action(function (Post $record): void {
            $record->publish();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'publish';
    }
}
