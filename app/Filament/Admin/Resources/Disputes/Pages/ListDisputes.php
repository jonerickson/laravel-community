<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Pages;

use App\Filament\Admin\Resources\Disputes\DisputeResource;
use App\Filament\Admin\Resources\Disputes\Widgets\DisputeFrequencyChart;
use App\Filament\Admin\Resources\Disputes\Widgets\DisputeOutcomeChart;
use App\Filament\Admin\Resources\Disputes\Widgets\DisputeStatsOverview;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListDisputes extends ListRecords
{
    protected static string $resource = DisputeResource::class;

    protected ?string $subheading = 'Manage payment disputes.';

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            DisputeStatsOverview::make(),
            DisputeFrequencyChart::make(),
            DisputeOutcomeChart::make(),
        ];
    }
}
