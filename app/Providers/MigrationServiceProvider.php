<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Migration\MigrationService;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunitySource;
use Illuminate\Support\ServiceProvider;
use Override;

class MigrationServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(function (): MigrationService {
            $service = new MigrationService;
            $service->registerSource(new InvisionCommunitySource);

            return $service;
        });
    }

    public function boot(): void
    {
        //
    }
}
