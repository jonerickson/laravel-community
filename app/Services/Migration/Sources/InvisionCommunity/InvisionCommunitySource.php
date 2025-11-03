<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity;

use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\Contracts\MigrationSource;
use App\Services\Migration\Sources\InvisionCommunity\Importers\BlogCommentImporter;
use App\Services\Migration\Sources\InvisionCommunity\Importers\BlogImporter;
use App\Services\Migration\Sources\InvisionCommunity\Importers\ForumImporter;
use App\Services\Migration\Sources\InvisionCommunity\Importers\GroupImporter;
use App\Services\Migration\Sources\InvisionCommunity\Importers\OrderImporter;
use App\Services\Migration\Sources\InvisionCommunity\Importers\PostImporter;
use App\Services\Migration\Sources\InvisionCommunity\Importers\ProductImporter;
use App\Services\Migration\Sources\InvisionCommunity\Importers\SubscriptionImporter;
use App\Services\Migration\Sources\InvisionCommunity\Importers\TopicImporter;
use App\Services\Migration\Sources\InvisionCommunity\Importers\UserImporter;

class InvisionCommunitySource implements MigrationSource
{
    protected array $importers = [];

    protected ?string $baseUrl = null;

    public function __construct()
    {
        $this->importers = [
            'groups' => new GroupImporter,
            'users' => new UserImporter,
            'blogs' => new BlogImporter,
            'blog_comments' => new BlogCommentImporter,
            'products' => new ProductImporter,
            'subscriptions' => new SubscriptionImporter,
            'forums' => new ForumImporter,
            'topics' => new TopicImporter,
            'posts' => new PostImporter,
            'orders' => new OrderImporter,
        ];

        $this->baseUrl = config('migration.sources.invision_community.base_url');
    }

    public function getName(): string
    {
        return 'invision-community';
    }

    public function getConnection(): string
    {
        return 'invision_community';
    }

    public function getImporters(): array
    {
        return $this->importers;
    }

    public function getImporter(string $entity): ?EntityImporter
    {
        return $this->importers[$entity] ?? null;
    }

    public function getSshConfig(): ?array
    {
        $host = config('migration.sources.invision_community.ssh.host');
        $user = config('migration.sources.invision_community.ssh.user');
        $port = config('migration.sources.invision_community.ssh.port', 22);
        $key = config('migration.sources.invision_community.ssh.key');

        if (! $host || ! $user) {
            return null;
        }

        return [
            'host' => $host,
            'user' => $user,
            'port' => $port,
            'key' => $key,
        ];
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(?string $url): void
    {
        $this->baseUrl = $url !== null && $url !== '' ? rtrim($url, '/') : null;
    }
}
