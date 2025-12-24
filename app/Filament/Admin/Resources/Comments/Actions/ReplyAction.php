<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Comments\Actions;

use App\Models\Comment;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Override;

class ReplyAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Reply');
        $this->color('info');
        $this->icon(Heroicon::OutlinedPencilSquare);
        $this->successNotificationTitle('Your reply was successfully added.');
        $this->modalDescription('Reply to the comment below.');
        $this->schema(fn (Comment $record): array => [
            TextEntry::make('comment')
                ->getStateUsing(fn () => $record->content)
                ->html()
                ->wrap(),
            Textarea::make('content')
                ->label('Reply')
                ->required()
                ->maxLength(65535),
        ]);
        $this->action(function (Comment $record, array $data, Action $action): void {
            /** @var Product $product */
            $product = $record->commentable;
            $product->comments()->create([
                'content' => data_get($data, 'content'),
                'parent_id' => $record->id,
            ]);

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'reply';
    }
}
