<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductType;
use App\Enums\SubscriptionInterval;
use App\Models\Price;
use App\Models\Product;
use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunityLanguageResolver;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionImporter implements EntityImporter
{
    protected const string ENTITY_NAME = 'subscriptions';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:subscription_map:';

    protected const string CACHE_TAG = 'migration:ic:subscriptions';

    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    public function __construct(
        protected ?InvisionCommunityLanguageResolver $languageResolver = null,
    ) {}

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

    public function getDependencies(): array
    {
        return [];
    }

    public function import(
        string $connection,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
        OutputStyle $output,
        MigrationResult $result,
    ): void {
        DB::connection($connection)->disableQueryLog();

        if (! $this->languageResolver instanceof InvisionCommunityLanguageResolver) {
            $this->languageResolver = new InvisionCommunityLanguageResolver($connection);
        }

        $baseQuery = DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('sp_enabled', 1)
            ->orderBy('sp_id')
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->offset($offset))
            ->when($limit !== null && $limit !== 0, fn ($builder) => $builder->limit($limit));

        $totalSubscriptions = $limit !== null && $limit !== 0 ? min($limit, $baseQuery->count()) : $baseQuery->count();

        $output->writeln("Found {$totalSubscriptions} subscription packages to migrate...");

        $progressBar = $output->createProgressBar($totalSubscriptions);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($batchSize, function ($subscriptions) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): bool {
            foreach ($subscriptions as $sourceSubscription) {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return false;
                }

                try {
                    $this->importSubscription($sourceSubscription, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);
                    $result->recordFailed(self::ENTITY_NAME, [
                        'source_id' => $sourceSubscription->sp_id ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import subscription', [
                        'source_id' => $sourceSubscription->sp_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import subscription: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();
        $output->newLine(2);
    }

    protected function importSubscription(object $sourceSubscription, bool $isDryRun, MigrationResult $result): void
    {
        $name = $this->languageResolver->resolveSubscriptionPackageName($sourceSubscription->sp_id, "Invision Subscription $sourceSubscription->sp_id");
        $slug = Str::slug($name);

        $existingProduct = Product::query()
            ->where('type', ProductType::Subscription)
            ->where(function ($query) use ($name, $slug): void {
                $query->where('name', $name)
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existingProduct) {
            $this->cacheSubscriptionMapping($sourceSubscription->sp_id, $existingProduct->id);
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceSubscription->sp_id,
                'name' => $name,
                'reason' => 'Already exists',
            ]);

            return;
        }

        $product = new Product;
        $product->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => "Subscription imported from Invision Community: $name",
            'type' => ProductType::Subscription,
            'is_featured' => (bool) $sourceSubscription->sp_featured,
            'approval_status' => ProductApprovalStatus::Approved,
            'approved_at' => Carbon::now(),
            'allow_promotion_codes' => false,
            'allow_discount_codes' => true,
            'trial_days' => 0,
            'commission_rate' => 0,
        ]);

        $prices = $this->createPrices($sourceSubscription, $product, $isDryRun, $result);

        if (! $isDryRun) {
            $product->save();

            /** @var Price $price */
            foreach ($prices as $price) {
                $price->save();

                $result->incrementMigrated('subscription_prices');
                $result->recordMigrated('subscription_prices', [
                    'product_id' => $product->id,
                    'price_id' => $price->id,
                    'amount' => $price->amount,
                    'currency' => $price->currency,
                    'interval' => $price->interval?->value ?? 'one-time',
                ]);
            }

            $this->cacheSubscriptionMapping($sourceSubscription->sp_id, $product->id);
        }

        $pricesSummary = collect($prices)->map(fn (Price $price): string => $price->amount.' '.$price->currency.' ('.($price->interval?->value ?? 'one-time').')')->implode(', ');

        $result->incrementMigrated(self::ENTITY_NAME);
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

    protected function createPrices(object $sourceSubscription, Product $product, bool $isDryRun, MigrationResult $result): array
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
                                ? "$currency $term {$interval->value}"
                                : "$currency One-Time",
                            'description' => null,
                            'amount' => $amount,
                            'currency' => $currency,
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
                            'name' => "$currency Monthly",
                            'description' => null,
                            'amount' => $amount,
                            'currency' => $currency,
                            'interval' => SubscriptionInterval::Monthly,
                            'interval_count' => 1,
                            'is_active' => true,
                            'is_default' => true,
                        ]);

                        $prices[] = $price;
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to create subscription prices', [
                'product_id' => $product->id ?? 'N/A',
                'source_subscription_id' => $sourceSubscription->sp_id,
                'error' => $e->getMessage(),
            ]);

            if (! $isDryRun) {
                $result->incrementFailed('subscription_prices');
                $result->recordFailed('subscription_prices', [
                    'product_id' => $product->id ?? 'N/A',
                    'source_subscription_id' => $sourceSubscription->sp_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $prices;
    }

    protected function cacheSubscriptionMapping(int $sourceSubscriptionId, int $targetProductId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceSubscriptionId, $targetProductId, self::CACHE_TTL);
    }
}
