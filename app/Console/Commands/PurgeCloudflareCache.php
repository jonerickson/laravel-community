<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CloudflareService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class PurgeCloudflareCache extends Command
{
    protected $signature = 'app:purge-cloudflare-cache';

    protected $description = 'Purges a specified cloudflare cache.';

    public function __construct(
        protected readonly CloudflareService $cloudflare,
    ) {
        parent::__construct();
    }

    /**
     * @throws ConnectionException
     */
    public function handle(): int
    {
        if ($this->cloudflare->purgeCache()) {
            $this->components->success('Cloudflare cache successfully purged.');

            return self::SUCCESS;
        }

        $this->components->error('Cloudflare purge cache failed.');

        return self::FAILURE;
    }
}
