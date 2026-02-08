<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Actions;

use App\Models\Dispute;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class GenerateDisputePackageAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Download Evidence PDF');
        $this->color('gray');
        $this->icon(Heroicon::OutlinedDocumentArrowDown);
        $this->url(
            fn (Dispute $record): string => route('admin.dispute-evidence.download', $record->order),
            shouldOpenInNewTab: true,
        );
    }

    public static function getDefaultName(): ?string
    {
        return 'generateDisputePackage';
    }
}
