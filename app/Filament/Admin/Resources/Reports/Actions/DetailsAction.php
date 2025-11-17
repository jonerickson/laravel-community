<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports\Actions;

use App\Models\Report;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Override;

class DetailsAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Details');
        $this->icon('heroicon-o-document-text');
        $this->color('gray');
        $this->modalHeading('Report Details');
        $this->modalDescription(fn (Report $record): string => sprintf('Report #%d - %s', $record->id, $record->reason->getLabel()));
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel('Close');
        $this->schema([
            TextEntry::make('additional_info')
                ->label('Report')
                ->default('There is no additional information.'),
            TextEntry::make('author')
                ->getStateUsing(fn (Report $record) => $record->getContentAuthor()->name ?? 'Unknown'),
            TextEntry::make('content')
                ->hintIcon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->hintAction(fn (): Action => Action::make('url')
                    ->url(fn (Report $record): ?string => $record->getUrl(), shouldOpenInNewTab: true)
                    ->label('Go to content'))
                ->getStateUsing(fn (Report $record): HtmlString => new HtmlString($record->getContent() ?? 'Unknown')),
        ]);
    }

    public static function getDefaultName(): ?string
    {
        return 'details';
    }
}
