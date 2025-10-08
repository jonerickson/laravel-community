<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Actions;

use App\Models\Post;
use Filament\Actions\Action;
use Override;

class ApproveAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-check');
        $this->color('success');
        $this->requiresConfirmation();
        $this->visible(fn (Post $record): bool => ! $record->is_approved);
        $this->successNotificationTitle('The post has been successfully approved.');
        $this->action(function (Post $record): void {
            $record->approve();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'approve';
    }
}
