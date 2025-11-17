<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\PriceType;
use App\Enums\ProductApprovalStatus;
use App\Enums\ProductTaxCode;
use App\Enums\ProductType;
use App\Enums\SubscriptionInterval;
use App\Models\Price;
use App\Models\Product;
use App\Services\Migration\AbstractImporter;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationConfig;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunitySource;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'subscriptions';

    public const string CACHE_KEY_PREFIX = 'migration:ic:subscription_map:';

    public const string CACHE_TAG = 'migration:ic:subscriptions';

    public static function getSubscriptionMapping(int $sourceSubscriptionId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceSubscriptionId);
    }

    public function isCompleted(): bool
    {
        return (bool) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.'completed');
    }

    public function markCompleted(): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.'completed', true, self::CACHE_TTL);
    }

    public function cleanup(): void
    {
        Cache::tags(self::CACHE_TAG)->flush();
    }

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getSourceTable(): string
    {
        return 'nexus_member_subscription_packages';
    }

    /**
     * @return ImporterDependency[]
     */
    public function getDependencies(): array
    {
        return [
            ImporterDependency::optionalPre('groups', 'Active subscribers can be automatically assigned to groups when purchasing a subscription'),
        ];
    }

    public function getTotalRecordsCount(): int
    {
        return $this->getBaseQuery()->count();
    }

    public function import(
        MigrationResult $result,
        OutputStyle $output,
        Factory $components,
    ): int {
        $config = $this->getConfig();

        $baseQuery = $this->getBaseQuery()
            ->when($config->offset !== null && $config->offset !== 0, fn ($builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn ($builder) => $builder->limit($config->limit));

        $totalSubscriptions = $baseQuery->clone()->countOffset();

        if ($output->isVerbose()) {
            $components->info(sprintf('Found %s subscription packages to migrate...', $totalSubscriptions));
        }

        $progressBar = $output->createProgressBar($totalSubscriptions);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($subscriptions) use ($config, $result, $progressBar, $output, $components, &$processed): bool {
            foreach ($subscriptions as $sourceSubscription) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importSubscription($sourceSubscription, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceSubscription->sp_id ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import subscription', [
                        'source_id' => $sourceSubscription->sp_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import subscription: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();

        $output->newLine(2);

        return $processed;
    }

    protected function importSubscription(object $sourceSubscription, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $name = Str::of($this->source instanceof InvisionCommunitySource
                ? $this->source->getLanguageResolver()->resolveSubscriptionPackageName($sourceSubscription->sp_id, 'Invision Subscription '.$sourceSubscription->sp_id)
                : 'Invision Subscription '.$sourceSubscription->sp_id)
            ->trim()
            ->limit(255, '')
            ->toString();

        $slug = Str::of($name)
            ->trim()
            ->limit(25, '')
            ->slug()
            ->toString();

        $existingProduct = Product::query()->where('slug', $slug)->first();

        if ($existingProduct) {
            $this->cacheSubscriptionMapping($sourceSubscription->sp_id, $existingProduct->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceSubscription->sp_id,
                    'name' => $name,
                    'slug' => $slug,
                    'reason' => 'Already exists',
                ]);
            }

            return;
        }

        $product = new Product;
        $product->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'type' => ProductType::Subscription,
            'is_featured' => (bool) $sourceSubscription->sp_featured,
            'is_subscription_only' => true,
            'approval_status' => ProductApprovalStatus::Approved,
            'approved_at' => Carbon::now(),
            'allow_promotion_codes' => false,
            'allow_discount_codes' => true,
            'trial_days' => 0,
            'commission_rate' => 0,
            'tax_code' => ProductTaxCode::SoftwareSaasPersonalUse,
        ]);

        if (! $config->isDryRun) {
            $product->save();
            $this->assignGroups($product, $sourceSubscription);
            $this->assignCategory($product);
            $this->cacheSubscriptionMapping($sourceSubscription->sp_id, $product->id);
        }

        $prices = $this->createPrices($sourceSubscription, $product, $config, $result, $output);

        if (! $config->isDryRun) {
            /** @var Price $price */
            foreach ($prices as $price) {
                $price->save();

                $result->incrementMigrated('subscription_prices');

                if ($output->isVeryVerbose()) {
                    $result->recordMigrated('subscription_prices', [
                        'product_id' => $product->id,
                        'price_id' => $price->id,
                        'type' => $price->type->value,
                        'amount' => $price->amount,
                        'currency' => $price->currency,
                        'interval' => $price->interval?->value ?? 'N/A',
                        'interval_count' => $price->interval_count ?? 'N/A',
                    ]);
                }
            }
        }

        $pricesSummary = collect($prices)->map(fn (Price $price): string => $price->amount.' '.$price->currency.' ('.($price->interval?->value ?? 'one-time').')')->implode(', ');

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourceSubscription->sp_id,
                'target_id' => $product->id ?? 'N/A (dry run)',
                'name' => $product->name,
                'slug' => $product->slug,
                'type' => $product->type->value,
                'is_featured' => $product->is_featured,
                'prices' => $pricesSummary ?: 'N/A',
            ]);
        }
    }

    protected function createPrices(object $sourceSubscription, Product $product, MigrationConfig $config, MigrationResult $result, OutputStyle $output): array
    {
        $prices = [];

        try {
            if (filled($sourceSubscription->sp_renew_options)) {
                $renewOptions = json_decode($sourceSubscription->sp_renew_options, true);

                if (is_array($renewOptions) && isset($renewOptions['cost'])) {
                    $term = (int) ($renewOptions['term'] ?? 1);
                    $unit = $renewOptions['unit'] ?? null;

                    $interval = null;
                    if ($unit === 'm') {
                        $interval = SubscriptionInterval::Monthly;
                    } elseif ($unit === 'y') {
                        $interval = SubscriptionInterval::Yearly;
                    }

                    foreach ($renewOptions['cost'] as $currencyCode => $priceData) {
                        if (! isset($priceData['amount'])) {
                            continue;
                        }

                        $amount = (float) $priceData['amount'];
                        $currency = strtoupper($priceData['currency'] ?? $currencyCode);

                        $price = new Price;
                        $price->forceFill([
                            'product_id' => $product->id,
                            'name' => $interval instanceof SubscriptionInterval
                                ? sprintf('%d %s', $term, $interval->value)
                                : 'One-Time',
                            'description' => null,
                            'amount' => $amount,
                            'currency' => $currency,
                            'type' => $interval instanceof SubscriptionInterval
                                ? PriceType::Recurring
                                : PriceType::OneTime,
                            'interval' => $interval,
                            'interval_count' => $term,
                            'is_active' => true,
                            'is_default' => true,
                        ]);

                        $prices[] = $price;
                    }
                }
            }

            if ($prices === [] && filled($sourceSubscription->sp_price)) {
                $basePrices = json_decode($sourceSubscription->sp_price, true);

                if (is_array($basePrices)) {
                    foreach ($basePrices as $currencyCode => $priceData) {
                        if (! isset($priceData['amount'])) {
                            continue;
                        }

                        $amount = (float) $priceData['amount'];
                        $currency = strtoupper((string) ($priceData['currency'] ?? $currencyCode));

                        $price = new Price;
                        $price->forceFill([
                            'product_id' => $product->id,
                            'name' => $currency.' Monthly',
                            'description' => null,
                            'amount' => $amount,
                            'currency' => $currency,
                            'type' => PriceType::Recurring,
                            'interval' => SubscriptionInterval::Monthly,
                            'interval_count' => 1,
                            'is_active' => true,
                            'is_default' => true,
                        ]);

                        $prices[] = $price;
                    }
                }
            }
        } catch (Exception $exception) {
            Log::error('Failed to create subscription prices', [
                'product_id' => $product->id ?? 'N/A',
                'source_subscription_id' => $sourceSubscription->sp_id,
                'error' => $exception->getMessage(),
            ]);

            if (! $config->isDryRun) {
                $result->incrementFailed('subscription_prices');

                if ($output->isVerbose()) {
                    $result->recordFailed('subscription_prices', [
                        'product_id' => $product->id ?? 'N/A',
                        'source_subscription_id' => $sourceSubscription->sp_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $prices;
    }

    protected function assignGroups(Product $product, object $sourceSubscription): void
    {
        $groupIds = [];

        if (! empty($sourceSubscription->sp_primary_group)) {
            foreach (array_filter(explode(',', (string) $sourceSubscription->sp_primary_group)) as $groupId) {
                $mappedGroupId = GroupImporter::getGroupMapping((int) $groupId);

                if ($mappedGroupId !== null && $mappedGroupId !== 0) {
                    $groupIds[] = $mappedGroupId;
                }
            }
        }

        if (! empty($sourceSubscription->sp_secondary_group)) {
            foreach (array_filter(explode(',', (string) $sourceSubscription->sp_secondary_group)) as $groupId) {
                $mappedGroupId = GroupImporter::getGroupMapping((int) $groupId);

                if ($mappedGroupId && ! in_array($mappedGroupId, $groupIds)) {
                    $groupIds[] = $mappedGroupId;
                }
            }
        }

        if ($groupIds !== []) {
            $product->groups()->sync(array_unique(array_values($groupIds)));
        }
    }

    protected function assignCategory(Product $product): void
    {
        $product->categories()->firstOrCreate([
            'name' => 'Subscriptions',
            'slug' => 'subscriptions',
        ], [
            'is_visible' => false,
        ]);
    }

    protected function cacheSubscriptionMapping(int $sourceSubscriptionId, int $targetProductId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceSubscriptionId, $targetProductId, self::CACHE_TTL);
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('sp_enabled', 1)
            ->orderBy('sp_id');
    }
}
