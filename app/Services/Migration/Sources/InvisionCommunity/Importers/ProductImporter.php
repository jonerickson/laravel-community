<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductType;
use App\Enums\SubscriptionInterval;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
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

class ProductImporter implements EntityImporter
{
    protected const string ENTITY_NAME = 'products';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:product_map:';

    protected const string CACHE_KEY_CATEGORY_PREFIX = 'migration:ic:product_category_map:';

    protected const string CACHE_TAG = 'migration:ic:products';

    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    public function __construct(
        protected ?InvisionCommunityLanguageResolver $languageResolver = null,
    ) {}

    public static function getProductMapping(int $sourceProductId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceProductId);
    }

    public static function getCategoryMapping(int $sourceCategoryId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_CATEGORY_PREFIX.$sourceCategoryId);
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
        return 'nexus_packages';
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

        $this->importCategories($connection, $batchSize, $limit, $offset, $isDryRun, $output, $result);

        $baseQuery = DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('p_store', 1)
            ->orderBy('p_id')
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->offset($offset))
            ->when($limit !== null && $limit !== 0, fn ($builder) => $builder->limit($limit));

        $totalProducts = $limit !== null && $limit !== 0 ? min($limit, $baseQuery->count()) : $baseQuery->count();

        $output->writeln("Found {$totalProducts} products to migrate...");

        $progressBar = $output->createProgressBar($totalProducts);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($batchSize, function ($products) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): bool {
            foreach ($products as $sourceProduct) {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return false;
                }

                try {
                    $this->importProduct($sourceProduct, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);
                    $result->recordFailed(self::ENTITY_NAME, [
                        'source_id' => $sourceProduct->p_id ?? 'unknown',
                        'name' => $sourceProduct->p_name ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import product', [
                        'source_id' => $sourceProduct->p_id ?? 'unknown',
                        'name' => $sourceProduct->p_name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import product: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();
        $output->newLine(2);
    }

    protected function importCategories(
        string $connection,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
        OutputStyle $output,
        MigrationResult $result,
    ): void {
        $baseQuery = DB::connection($connection)
            ->table('nexus_package_groups')
            ->orderBy('pg_id')
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->offset($offset))
            ->when($limit !== null && $limit !== 0, fn ($builder) => $builder->limit($limit));

        $totalCategories = $limit !== null && $limit !== 0 ? min($limit, $baseQuery->count()) : $baseQuery->count();

        $output->writeln("Found {$totalCategories} product categories to migrate...");

        $progressBar = $output->createProgressBar($totalCategories);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($batchSize, function ($categories) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): bool {
            foreach ($categories as $sourceCategory) {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return false;
                }

                try {
                    $this->importCategory($sourceCategory, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed('product_categories');
                    $result->recordFailed('product_categories', [
                        'source_id' => $sourceCategory->pg_id ?? 'unknown',
                        'name' => $sourceCategory->pg_name ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import product category', [
                        'source_id' => $sourceCategory->pg_id ?? 'unknown',
                        'name' => $sourceCategory->pg_name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import product category: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();
        $output->newLine(2);
    }

    protected function importCategory(object $sourceCategory, bool $isDryRun, MigrationResult $result): void
    {
        $name = $this->languageResolver?->resolveProductGroupName($sourceCategory->pg_id, "Invision Product Group $sourceCategory->pg_id");
        $slug = $sourceCategory->pg_seo_name ?? Str::slug($name);

        $existingCategory = ProductCategory::query()
            ->where(function ($query) use ($name, $slug): void {
                $query->where('name', $name)
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existingCategory) {
            $this->cacheCategoryMapping($sourceCategory->pg_id, $existingCategory->id);
            $result->incrementSkipped('product_categories');
            $result->recordSkipped('product_categories', [
                'source_id' => $sourceCategory->pg_id,
                'name' => $name,
                'reason' => 'Already exists',
            ]);

            return;
        }

        $category = new ProductCategory;
        $category->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'order' => $sourceCategory->pg_position ?? 0,
            'is_active' => true,
        ]);

        if (! $isDryRun) {
            $category->save();
            $this->cacheCategoryMapping($sourceCategory->pg_id, $category->id);
        }

        $result->incrementMigrated('product_categories');
        $result->recordMigrated('product_categories', [
            'source_id' => $sourceCategory->pg_id,
            'target_id' => $category->id ?? 'N/A (dry run)',
            'name' => $category->name,
            'slug' => $category->slug,
        ]);
    }

    protected function importProduct(object $sourceProduct, bool $isDryRun, MigrationResult $result): void
    {
        $name = $this->languageResolver->resolveProductName($sourceProduct->p_id, "Invision Product $sourceProduct->p_id");
        $slug = $sourceProduct->p_seo_name ?? Str::slug($name);

        $existingProduct = Product::query()
            ->where(function ($query) use ($name, $slug): void {
                $query->where('name', $name)
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existingProduct) {
            $this->cacheProductMapping($sourceProduct->p_id, $existingProduct->id);
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceProduct->p_id,
                'name' => $name,
                'reason' => 'Already exists',
            ]);

            return;
        }

        $description = $this->cleanHtml($sourceProduct->p_page ?? '');

        $product = new Product;
        $product->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => $description !== null && $description !== '' && $description !== '0' ? $description : "Product imported from Invision Community: $name",
            'type' => ProductType::Product,
            'is_featured' => (bool) $sourceProduct->p_featured,
            'approval_status' => ProductApprovalStatus::Approved,
            'approved_at' => Carbon::now(),
            'allow_promotion_codes' => false,
            'allow_discount_codes' => true,
            'trial_days' => 0,
            'commission_rate' => 0,
            'created_at' => $sourceProduct->p_date_added
                ? Carbon::createFromTimestamp($sourceProduct->p_date_added)
                : Carbon::now(),
            'updated_at' => $sourceProduct->p_date_updated
                ? Carbon::createFromTimestamp($sourceProduct->p_date_updated)
                : Carbon::now(),
        ]);

        if (! $isDryRun) {
            $product->save();

            if ($sourceProduct->p_group) {
                $categoryId = static::getCategoryMapping($sourceProduct->p_group);
                if ($categoryId !== null && $categoryId !== 0) {
                    $product->categories()->attach($categoryId);
                }
            }
        }

        $prices = $this->createPrices($sourceProduct, $product, $isDryRun, $result);

        if (! $isDryRun) {
            /** @var Price $price */
            foreach ($prices as $price) {
                $price->save();

                $result->incrementMigrated('product_prices');
                $result->recordMigrated('product_prices', [
                    'product_id' => $product->id,
                    'price_id' => $price->id,
                    'amount' => $price->amount,
                    'currency' => $price->currency,
                    'interval' => $price->interval?->value ?? 'one-time',
                ]);
            }

            $this->cacheProductMapping($sourceProduct->p_id, $product->id);
        }

        $pricesSummary = collect($prices)->map(fn (Price $price): string => ($price->getOriginal('amount') / 100).' '.$price->currency.' ('.($price->interval?->value ?? 'one-time').')')->implode(', ');

        $result->incrementMigrated(self::ENTITY_NAME);
        $result->recordMigrated(self::ENTITY_NAME, [
            'source_id' => $sourceProduct->p_id,
            'target_id' => $product->id ?? 'N/A (dry run)',
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type->value,
            'is_featured' => $product->is_featured,
            'category_id' => $sourceProduct->p_group ?? 'N/A',
            'prices' => $pricesSummary ?: 'N/A',
        ]);
    }

    protected function createPrices(object $sourceProduct, Product $product, bool $isDryRun, MigrationResult $result): array
    {
        $prices = [];

        try {
            if (filled($sourceProduct->p_renew_options)) {
                $renewOptions = json_decode($sourceProduct->p_renew_options, true);

                if (is_array($renewOptions)) {
                    foreach ($renewOptions as $index => $renewOption) {
                        if (! isset($renewOption['cost'])) {
                            continue;
                        }
                        if (! is_array($renewOption['cost'])) {
                            continue;
                        }
                        foreach ($renewOption['cost'] as $currencyCode => $priceData) {
                            if (! isset($priceData['amount'])) {
                                continue;
                            }

                            $amount = (float) $priceData['amount'];
                            $currency = strtoupper((string) ($priceData['currency'] ?? $currencyCode));

                            $interval = null;
                            $intervalCount = (int) ($renewOption['term'] ?? 1);

                            $unit = $renewOption['unit'] ?? null;

                            if ($unit === 'm') {
                                $interval = SubscriptionInterval::Monthly;
                            } elseif ($unit === 'y') {
                                $interval = SubscriptionInterval::Yearly;
                            }

                            $price = new Price;
                            $price->forceFill([
                                'product_id' => $product->id,
                                'name' => $interval instanceof SubscriptionInterval
                                    ? "$currency $intervalCount {$interval->value}"
                                    : "$currency One-Time",
                                'description' => null,
                                'amount' => $amount,
                                'currency' => $currency,
                                'interval' => $interval,
                                'interval_count' => $intervalCount,
                                'is_active' => true,
                                'is_default' => $index === 0,
                            ]);

                            $prices[] = $price;
                        }
                    }
                }
            }

            if ($prices === [] && filled($sourceProduct->p_base_price)) {
                $basePrices = json_decode($sourceProduct->p_base_price, true);

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
                            'name' => "$currency One-Time",
                            'description' => null,
                            'amount' => $amount,
                            'currency' => $currency,
                            'interval' => null,
                            'interval_count' => 1,
                            'is_active' => true,
                            'is_default' => true,
                        ]);

                        $prices[] = $price;
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to create product prices', [
                'product_id' => $product->id ?? 'N/A',
                'source_product_id' => $sourceProduct->p_id,
                'error' => $e->getMessage(),
            ]);

            if (! $isDryRun) {
                $result->incrementFailed('product_prices');
                $result->recordFailed('product_prices', [
                    'product_id' => $product->id ?? 'N/A',
                    'source_product_id' => $sourceProduct->p_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $prices;
    }

    protected function cleanHtml(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        return $html;
    }

    protected function cacheProductMapping(int $sourceProductId, int $targetProductId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceProductId, $targetProductId, self::CACHE_TTL);
    }

    protected function cacheCategoryMapping(int $sourceCategoryId, int $targetCategoryId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_CATEGORY_PREFIX.$sourceCategoryId, $targetCategoryId, self::CACHE_TTL);
    }
}
