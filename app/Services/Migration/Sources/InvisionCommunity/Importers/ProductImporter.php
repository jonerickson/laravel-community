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
use App\Models\ProductCategory;
use App\Services\Migration\AbstractImporter;
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

class ProductImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'products';

    public const string CACHE_KEY_PREFIX = 'migration:ic:product_map:';

    public const string CACHE_KEY_CATEGORY_PREFIX = 'migration:ic:product_category_map:';

    public const string CACHE_TAG = 'migration:ic:products';

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

    /**
     * @return array{}
     */
    public function getDependencies(): array
    {
        return [];
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
        $this->importCategories($result, $output, $components);

        $config = $this->getConfig();

        $baseQuery = $this->getBaseQuery()
            ->when($config->offset !== null && $config->offset !== 0, fn ($builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn ($builder) => $builder->limit($config->limit));

        $totalProducts = $baseQuery->clone()->countOffset();

        $components->info(sprintf('Found %s products to migrate...', $totalProducts));

        $progressBar = $output->createProgressBar($totalProducts);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($products) use ($config, $result, $progressBar, $output, $components, &$processed): bool {
            foreach ($products as $sourceProduct) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importProduct($sourceProduct, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceProduct->p_id ?? 'unknown',
                            'name' => $sourceProduct->p_name ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import product', [
                        'source_id' => $sourceProduct->p_id ?? 'unknown',
                        'name' => $sourceProduct->p_name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import product: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
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

    protected function importCategories(
        MigrationResult $result,
        OutputStyle $output,
        Factory $components,
    ): void {
        $connection = $this->source->getConnection();
        $config = $this->getConfig();

        $baseQuery = DB::connection($connection)
            ->table('nexus_package_groups')
            ->orderBy('pg_id')
            ->when($config->offset !== null && $config->offset !== 0, fn ($builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn ($builder) => $builder->limit($config->limit));

        $totalCategories = $baseQuery->clone()->countOffset();

        if ($output->isVerbose()) {
            $components->info(sprintf('Found %s product categories to migrate...', $totalCategories));
        }

        $progressBar = $output->createProgressBar($totalCategories);
        $progressBar->start();

        $processed = 0;
        $processedSourceCategories = [];

        $baseQuery->chunk($config->batchSize, function ($categories) use ($config, $result, $progressBar, $output, $components, &$processed, &$processedSourceCategories): bool {
            foreach ($categories as $sourceCategory) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importCategory($sourceCategory, $config, $result, $output);
                    $processedSourceCategories[] = $sourceCategory;
                } catch (Exception $e) {
                    $result->incrementFailed('product_categories');

                    if ($output->isVerbose()) {
                        $result->recordFailed('product_categories', [
                            'source_id' => $sourceCategory->pg_id ?? 'unknown',
                            'name' => $sourceCategory->pg_name ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import product category', [
                        'source_id' => $sourceCategory->pg_id ?? 'unknown',
                        'name' => $sourceCategory->pg_name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import product category: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $output->newLine(2);

        if ($output->isVerbose()) {
            $components->info(sprintf('Migrated %d product categories...', $processed));
        }

        $this->updateCategoryParentRelationships($processedSourceCategories, $config, $output, $components);
    }

    protected function importCategory(object $sourceCategory, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $name = Str::of($this->source instanceof InvisionCommunitySource
                ? $this->source->getLanguageResolver()->resolveProductGroupName($sourceCategory->pg_id, 'Invision Product Group '.$sourceCategory->pg_id)
                : 'Invision Product Group '.$sourceCategory->pg_id)
            ->trim()
            ->limit(255, '')
            ->toString();

        $description = $this->source instanceof InvisionCommunitySource
            ? $this->source->getLanguageResolver()->resolveProductGroupDescription($sourceCategory->pg_id)
            : null;

        $slug = Str::of($sourceCategory->pg_seo_name ?? $name)
            ->trim()
            ->limit(25, '')
            ->slug()
            ->toString();

        $existingCategory = ProductCategory::query()->where('slug', $slug)->first();

        if ($existingCategory) {
            $this->cacheCategoryMapping($sourceCategory->pg_id, $existingCategory->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceCategory->pg_id,
                    'name' => $name,
                    'slug' => $slug,
                    'reason' => 'Already exists',
                ]);
            }

            return;
        }

        $category = new ProductCategory;
        $category->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => Str::of($description)->stripTags()->toString() ?: null,
            'order' => $sourceCategory->pg_position ?? 0,
            'is_active' => true,
        ]);

        if (! $config->isDryRun) {
            $category->save();
            $this->cacheCategoryMapping($sourceCategory->pg_id, $category->id);

            if (($imagePath = $sourceCategory->pg_image) && ($baseUrl = $this->source->getBaseUrl()) && $config->downloadMedia) {
                $filePath = $this->downloadAndStoreFile(
                    baseUrl: $baseUrl.'/uploads',
                    sourcePath: $imagePath,
                    storagePath: 'products/categories',
                );

                if (! is_null($filePath)) {
                    $category->featured_image = $filePath;
                    $category->save();
                }
            }
        }

        $result->incrementMigrated('product_categories');

        if ($output->isVeryVerbose()) {
            $result->recordMigrated('product_categories', [
                'source_id' => $sourceCategory->pg_id,
                'target_id' => $category->id ?? 'N/A (dry run)',
                'name' => $category->name,
                'slug' => $category->slug,
            ]);
        }
    }

    /**
     * @param  object[]  $sourceCategories
     */
    protected function updateCategoryParentRelationships(array $sourceCategories, MigrationConfig $config, OutputStyle $output, Factory $components): void
    {
        if ($sourceCategories === []) {
            return;
        }

        $components->info('Updating product category parent relationships...');

        $progressBar = $output->createProgressBar(count($sourceCategories));
        $progressBar->start();

        foreach ($sourceCategories as $sourceCategory) {
            try {
                $mappedCategoryId = static::getCategoryMapping((int) $sourceCategory->pg_id);

                if ($mappedCategoryId === null || $mappedCategoryId === 0) {
                    $progressBar->advance();

                    continue;
                }

                if (isset($sourceCategory->pg_parent) && $sourceCategory->pg_parent !== null && $sourceCategory->pg_parent !== 0) {
                    $parentCategoryId = static::getCategoryMapping((int) $sourceCategory->pg_parent);

                    if ($parentCategoryId !== null && $parentCategoryId !== 0 && ! $config->isDryRun) {
                        ProductCategory::query()
                            ->where('id', $mappedCategoryId)
                            ->update(['parent_id' => $parentCategoryId]);
                    }
                }
            } catch (Exception $e) {
                Log::error('Failed to update product category parent relationship', [
                    'source_id' => $sourceCategory->pg_id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $output->newLine(2);
                $fileName = Str::of($e->getFile())->classBasename();
                $components->error(sprintf('Failed to update product parent category relationship: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->newLine(2);
        $components->info('Updating product category parent relationships complete.');
    }

    protected function importProduct(object $sourceProduct, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $name = Str::of($this->source instanceof InvisionCommunitySource
                ? $this->source->getLanguageResolver()->resolveProductName($sourceProduct->p_id, 'Invision Product '.$sourceProduct->p_id)
                : 'Invision Product '.$sourceProduct->p_id)
            ->trim()
            ->limit(255, '')
            ->toString();

        $slug = Str::of($sourceProduct->p_seo_name ?? $name)
            ->trim()
            ->limit(25, '')
            ->slug()
            ->toString();

        $existingProduct = Product::query()->where('slug', $slug)->first();

        if ($existingProduct) {
            $this->cacheProductMapping($sourceProduct->p_id, $existingProduct->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceProduct->p_id,
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
            'description' => Str::of($sourceProduct->p_page)->stripTags()->toString() ?: null,
            'type' => ProductType::Product,
            'is_featured' => (bool) $sourceProduct->p_featured,
            'approval_status' => ProductApprovalStatus::Approved,
            'approved_at' => Carbon::now(),
            'allow_promotion_codes' => false,
            'allow_discount_codes' => true,
            'trial_days' => 0,
            'commission_rate' => 0,
            'tax_code' => ProductTaxCode::SoftwareSaasPersonalUse,
            'created_at' => $sourceProduct->p_date_added
                ? Carbon::createFromTimestamp($sourceProduct->p_date_added)
                : Carbon::now(),
            'updated_at' => $sourceProduct->p_date_updated
                ? Carbon::createFromTimestamp($sourceProduct->p_date_updated)
                : Carbon::now(),
        ]);

        if (! $config->isDryRun) {
            $product->save();
            $this->assignCategory($product, $sourceProduct);
            $this->assignGroups($product, $sourceProduct);

            if (($imagePath = $sourceProduct->p_image) && ($baseUrl = $this->source->getBaseUrl()) && $config->downloadMedia) {
                $filePath = $this->downloadAndStoreFile(
                    baseUrl: $baseUrl.'/uploads',
                    sourcePath: $imagePath,
                    storagePath: 'products',
                );

                if (! is_null($filePath)) {
                    $product->featured_image = $filePath;
                    $product->save();
                }
            }
        }

        $prices = $this->createPrices($sourceProduct, $product, $config, $result, $output);

        if (! $config->isDryRun) {
            /** @var Price $price */
            foreach ($prices as $price) {
                $price->save();

                $result->incrementMigrated('product_prices');

                if ($output->isVeryVerbose()) {
                    $result->recordMigrated('product_prices', [
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

            $this->cacheProductMapping($sourceProduct->p_id, $product->id);
        }

        $pricesSummary = collect($prices)->map(fn (Price $price): string => $price->amount.' '.$price->currency.' ('.($price->interval?->value ?? 'one-time').')')->implode(', ');

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
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
    }

    protected function createPrices(object $sourceProduct, Product $product, MigrationConfig $config, MigrationResult $result, OutputStyle $output): array
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
                                    ? sprintf('%d %s', $intervalCount, $interval->value)
                                    : 'One-Time',
                                'description' => null,
                                'amount' => $amount,
                                'currency' => $currency,
                                'type' => PriceType::Recurring,
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
                            'name' => 'One-Time',
                            'description' => null,
                            'amount' => $amount,
                            'currency' => $currency,
                            'type' => PriceType::OneTime,
                            'interval' => null,
                            'interval_count' => 1,
                            'is_active' => true,
                            'is_default' => true,
                        ]);

                        $prices[] = $price;
                    }
                }
            }
        } catch (Exception $exception) {
            Log::error('Failed to create product prices', [
                'product_id' => $product->id ?? 'N/A',
                'source_product_id' => $sourceProduct->p_id,
                'error' => $exception->getMessage(),
            ]);

            if (! $config->isDryRun) {
                $result->incrementFailed('product_prices');

                if ($output->isVerbose()) {
                    $result->recordFailed('product_prices', [
                        'product_id' => $product->id ?? 'N/A',
                        'source_product_id' => $sourceProduct->p_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $prices;
    }

    protected function assignGroups(Product $product, object $sourceProduct): void
    {
        $groupIds = [];

        if (! empty($sourceProduct->p_primary_group)) {
            foreach (array_filter(explode(',', (string) $sourceProduct->p_primary_group)) as $groupId) {
                $mappedGroupId = GroupImporter::getGroupMapping((int) $groupId);

                if ($mappedGroupId !== null && $mappedGroupId !== 0) {
                    $groupIds[] = $mappedGroupId;
                }
            }
        }

        if (! empty($sourceProduct->p_secondary_group)) {
            foreach (array_filter(explode(',', (string) $sourceProduct->p_secondary_group)) as $groupId) {
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

    protected function assignCategory(Product $product, object $sourceProduct): void
    {
        if ($sourceProduct->p_group) {
            $categoryId = static::getCategoryMapping($sourceProduct->p_group);
            if ($categoryId !== null && $categoryId !== 0) {
                $product->categories()->attach($categoryId);
            }
        }
    }

    protected function cacheProductMapping(int $sourceProductId, int $targetProductId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceProductId, $targetProductId, self::CACHE_TTL);
    }

    protected function cacheCategoryMapping(int $sourceCategoryId, int $targetCategoryId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_CATEGORY_PREFIX.$sourceCategoryId, $targetCategoryId, self::CACHE_TTL);
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('p_store', 1)
            ->orderBy('p_id');
    }
}
