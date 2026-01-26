<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Resources\Products\Pages;

use App\Enums\ProductApprovalStatus;
use App\Filament\Marketplace\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected ?string $subheading = 'Manage your community-provided marketplace products.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Submit product')
                ->slideOver()
                ->createAnother(false)
                ->modalHeading('Submit Product')
                ->modalDescription('Submit a new project to the marketplace store. Your product will need to be reviewed by staff before it can be purchased.')
                ->modalSubmitActionLabel('Submit')
                ->mutateDataUsing(function (array $data): array {
                    $data['seller_id'] = Auth::id();
                    $data['approval_status'] = ProductApprovalStatus::Pending;

                    return $data;
                }),
        ];
    }
}
