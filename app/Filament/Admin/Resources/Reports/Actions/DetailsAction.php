<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports\Actions;

use App\Models\Report;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;

class DetailsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Details');
        $this->icon('heroicon-o-document-text');
        $this->color('gray');
        $this->modalHeading('Report Details');
        $this->modalDescription(fn (Report $record): string => "Report #{$record->id} - {$record->reason->getLabel()}");
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel('Close');
        $this->schema([
            TextEntry::make('additional_info')
                ->hiddenLabel()
                ->default('There is no additional information.'),
        ]);
    }

    public static function getDefaultName(): ?string
    {
        return 'details';
    }
}
