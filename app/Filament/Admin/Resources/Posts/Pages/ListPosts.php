<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Pages;

use App\Filament\Admin\Resources\Posts\PostResource;
use App\Filament\Admin\Resources\Posts\Widgets\PostStatsOverview;
use App\Filament\Imports\PostImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected ?string $subheading = 'Manage your blog posts and news articles.';

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(PostImporter::class),
            CreateAction::make(),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            PostStatsOverview::make(),
        ];
    }
}
