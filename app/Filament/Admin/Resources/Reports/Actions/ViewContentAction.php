<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports\Actions;

use App\Models\Report;
use Filament\Actions\Action;
use Override;

class ViewContentAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('View Content');
        $this->icon('heroicon-o-eye');
        $this->color('info');
        $this->url(fn (Report $record): ?string => $record->getUrl());
        $this->openUrlInNewTab();
        $this->visible(fn (Report $record): bool => $record->getUrl() !== null);
    }

    public static function getDefaultName(): ?string
    {
        return 'view_content';
    }
}
