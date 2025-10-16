<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InvisionCommunityLanguageResolver
{
    protected const string CACHE_KEY_PREFIX = 'migration:ic:lang:';

    protected const int CACHE_TTL = 3600;

    protected array $languageCache = [];

    public function __construct(
        protected string $connection,
        protected ?int $defaultLanguageId = null,
    ) {
        if ($this->defaultLanguageId === null) {
            $this->defaultLanguageId = $this->getDefaultLanguageId();
        }
    }

    public function resolve(string $key, ?string $fallback = null): ?string
    {
        if (isset($this->languageCache[$key])) {
            return $this->languageCache[$key];
        }

        $cacheKey = self::CACHE_KEY_PREFIX.$key;

        $value = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key): ?string {
            $result = DB::connection($this->connection)
                ->table('core_sys_lang_words')
                ->where('word_key', $key)
                ->where('lang_id', $this->defaultLanguageId)
                ->value('word_default');

            return $result ? (string) $result : null;
        });

        $this->languageCache[$key] = $value ?? $fallback;

        return $this->languageCache[$key];
    }

    public function resolveGroupName(int|string $groupId): ?string
    {
        return $this->resolve("core_group_$groupId");
    }

    public function resolveProductGroupName(int|string $groupId): ?string
    {
        return $this->resolve("nexus_pgroup_$groupId");
    }

    public function resolveProductName(int|string $groupId): ?string
    {
        return $this->resolve("nexus_package_$groupId");
    }

    public function resolveSubscriptionPackageName(int|string $packageId): ?string
    {
        return $this->resolve("nexus_subs_$packageId");
    }

    public function batchResolve(array $keys): array
    {
        $missingKeys = array_diff($keys, array_keys($this->languageCache));

        if ($missingKeys === []) {
            return array_intersect_key($this->languageCache, array_flip($keys));
        }

        $results = DB::connection($this->connection)
            ->table('core_sys_lang_words')
            ->whereIn('word_key', $missingKeys)
            ->where('lang_id', $this->defaultLanguageId)
            ->pluck('word_default', 'word_key')
            ->toArray();

        foreach ($missingKeys as $key) {
            $value = $results[$key] ?? null;
            $this->languageCache[$key] = $value;

            $cacheKey = self::CACHE_KEY_PREFIX.$key;
            Cache::put($cacheKey, $value, self::CACHE_TTL);
        }

        return array_intersect_key($this->languageCache, array_flip($keys));
    }

    public function clearCache(): void
    {
        $this->languageCache = [];
    }

    protected function getDefaultLanguageId(): int
    {
        return (int) DB::connection($this->connection)
            ->table('core_sys_lang')
            ->where('lang_default', 1)
            ->value('lang_id') ?? 1;
    }
}
