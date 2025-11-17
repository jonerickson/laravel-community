<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InvisionCommunityLanguageResolver
{
    public const string CACHE_KEY_PREFIX = 'migration:ic:lang:';

    public const string CACHE_TAG = 'migration:ic:lang';

    protected const int CACHE_TTL = 3600;

    public function __construct(
        protected string $connection,
        protected ?int $defaultLanguageId = null,
    ) {
        //
    }

    public function resolve(string $key, ?string $fallback = null): ?string
    {
        $value = Cache::tags(self::CACHE_TAG)->remember(self::CACHE_KEY_PREFIX.$key, self::CACHE_TTL, function () use ($key): ?string {
            $result = DB::connection($this->connection)
                ->table('core_sys_lang_words')
                ->where('word_key', $key)
                ->where('lang_id', $this->getDefaultLanguageId())
                ->value('word_default');

            return $result ? (string) $result : null;
        });

        return $value ?? $fallback;
    }

    public function resolveGroupName(int|string $groupId, ?string $fallback = null): ?string
    {
        return $this->resolve('core_group_'.$groupId, $fallback);
    }

    public function resolveProductGroupName(int|string $groupId, ?string $fallback = null): ?string
    {
        return $this->resolve('nexus_pgroup_'.$groupId, $fallback);
    }

    public function resolveProductGroupDescription(int|string $groupId, ?string $fallback = null): ?string
    {
        return $this->resolve(sprintf('nexus_pgroup_%s_desc', $groupId), $fallback);
    }

    public function resolveProductName(int|string $productId, ?string $fallback = null): ?string
    {
        return $this->resolve('nexus_package_'.$productId, $fallback);
    }

    public function resolveSubscriptionPackageName(int|string $packageId, ?string $fallback = null): ?string
    {
        return $this->resolve('nexus_subs_'.$packageId, $fallback);
    }

    public function resolveForumName(int|string $forumId, ?string $fallback = null): ?string
    {
        return $this->resolve('forums_forum_'.$forumId, $fallback);
    }

    public function resolveForumDescription(int|string $forumId, ?string $fallback = null): ?string
    {
        return $this->resolve(sprintf('forums_forum_%s_desc', $forumId), $fallback);
    }

    public function batchResolve(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $cacheKey = self::CACHE_KEY_PREFIX.$key;

            $value = Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function () use ($key): ?string {
                $result = DB::connection($this->connection)
                    ->table('core_sys_lang_words')
                    ->where('word_key', $key)
                    ->where('lang_id', $this->getDefaultLanguageId())
                    ->value('word_default');

                return $result ? (string) $result : null;
            });

            $result[$key] = $value;
        }

        return $result;
    }

    public function cleanup(): void
    {
        Cache::tags(self::CACHE_TAG)->flush();
    }

    protected function getDefaultLanguageId(): int
    {
        if ($this->defaultLanguageId !== null) {
            return $this->defaultLanguageId;
        }

        return $this->defaultLanguageId = (int) DB::connection($this->connection)
            ->table('core_sys_lang')
            ->where('lang_default', 1)
            ->value('lang_id') ?? 1;
    }
}
