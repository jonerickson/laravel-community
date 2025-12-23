<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use App\Services\Migration\AbstractImporter;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationConfig;
use App\Services\Migration\MigrationResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'orders';

    public const string CACHE_KEY_PREFIX = 'migration:ic:order_map:';

    public const string CACHE_TAG = 'migration:ic:orders';

    public static function getOrderMapping(int $sourceOrderId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceOrderId);
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
        return 'nexus_invoices';
    }

    /**
     * @return ImporterDependency[]
     */
    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'Orders require users to exist for customer assignment'),
            ImporterDependency::requiredPre('products', 'Orders require products to exist for order items'),
            ImporterDependency::optionalPre('subscriptions', 'Orders may be linked to a subscription plan'),
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

        $totalOrders = $baseQuery->clone()->countOffset();

        if ($output->isVerbose()) {
            $components->info(sprintf('Found %s paid orders to migrate...', $totalOrders));
        }

        $progressBar = $output->createProgressBar($totalOrders);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($orders) use ($config, $result, $progressBar, $output, $components, &$processed): bool {
            foreach ($orders as $sourceOrder) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importOrder($sourceOrder, $config, $result, $output, $components);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceOrder->i_id ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import order', [
                        'source_id' => $sourceOrder->i_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import order: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
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

    protected function importOrder(object $sourceOrder, MigrationConfig $config, MigrationResult $result, OutputStyle $output, Factory $components): void
    {
        $user = $this->findUser($sourceOrder);

        if (! $user instanceof User) {
            $result->incrementFailed(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordFailed(self::ENTITY_NAME, [
                    'source_id' => $sourceOrder->i_id,
                    'error' => 'Could not find user',
                ]);
            }

            return;
        }

        $invoiceNumber = $sourceOrder->i_id ?: null;

        $existingOrder = Order::query()->where('invoice_number', $invoiceNumber)->first();

        if ($existingOrder && $invoiceNumber) {
            $this->cacheOrderMapping($sourceOrder->i_id, $existingOrder->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceOrder->i_id,
                    'user' => $user->name,
                    'status' => $existingOrder->status->value,
                    'total' => $sourceOrder->i_total,
                    'currency' => $sourceOrder->i_currency,
                    'reason' => 'Already exists',
                ]);
            }

            return;
        }

        $order = new Order;
        $order->forceFill([
            'user_id' => $user->id,
            'status' => $this->getOrderStatus($sourceOrder),
            'amount_due' => (float) $sourceOrder->i_total,
            'amount_paid' => (float) $sourceOrder->i_total,
            'amount_overpaid' => 0,
            'amount_remaining' => 0,
            'invoice_number' => $invoiceNumber,
            'external_order_id' => $sourceOrder->i_id,
            'external_invoice_id' => $sourceOrder->i_id,
            'created_at' => $sourceOrder->i_date
                ? Carbon::createFromTimestamp($sourceOrder->i_date)
                : Carbon::now(),
            'updated_at' => $sourceOrder->i_paid
                ? Carbon::createFromTimestamp($sourceOrder->i_paid)
                : ($sourceOrder->i_date
                    ? Carbon::createFromTimestamp($sourceOrder->i_date)
                    : Carbon::now()),
        ]);

        if (! $config->isDryRun) {
            $order->save();
            $this->cacheOrderMapping($sourceOrder->i_id, $order->id);
        }

        $orderItems = $this->createOrderItems($sourceOrder, $order, $output, $components);

        if (! $config->isDryRun) {
            /** @var OrderItem $orderItem */
            foreach ($orderItems as $orderItem) {
                $orderItem->save();

                $result->incrementMigrated('order_items');

                if ($output->isVeryVerbose()) {
                    $result->recordMigrated('order_items', [
                        'order_id' => $order->id,
                        'price_id' => $orderItem->price_id,
                        'product_name' => $orderItem->name,
                        'amount' => $orderItem->amount,
                    ]);
                }
            }
        }

        $orderItemsSummary = collect($orderItems)->map(fn (OrderItem $item): string => ($item->name ?? 'Unknown').' - '.$item->amount)->implode(', ');

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourceOrder->i_id,
                'target_id' => $order->id ?? 'N/A (dry run)',
                'user' => $user->name,
                'status' => $order->status->value,
                'total' => $sourceOrder->i_total,
                'currency' => $sourceOrder->i_currency,
                'items' => $orderItemsSummary ?: 'N/A',
            ]);
        }
    }

    protected function getOrderStatus(object $sourceOrder): OrderStatus
    {
        return match ($sourceOrder->i_status) {
            'paid' => OrderStatus::Succeeded,
            'canc' => OrderStatus::Cancelled,
            'expd' => OrderStatus::Expired,
            default => OrderStatus::Pending,
        };
    }

    protected function createOrderItems(object $sourceOrder, Order $order, OutputStyle $output, Factory $components): array
    {
        $orderItems = [];

        try {
            $items = json_decode($sourceOrder->i_items ?? '', true);

            if (empty($items)) {
                return $orderItems;
            }

            foreach ($items as $item) {
                $itemId = $item['itemID'] ?? null;
                $itemType = $item['type'] ?? null;
                $itemAction = $item['act'] ?? null;

                if (! $itemId || ! $itemType) {
                    $orderItems[] = $this->createOrderItem($order, $item, $sourceOrder, null, null);

                    continue;
                }

                $product = $this->findProductByItemType($sourceOrder, $itemType, $itemId, $itemAction);

                if (! $product instanceof Product) {
                    $orderItems[] = $this->createOrderItem($order, $item, $sourceOrder, null, null);

                    continue;
                }

                $amount = (float) ($item['cost'] ?? 0);
                $price = $this->findPriceForProduct($product, $amount, $sourceOrder->i_currency);

                $orderItems[] = $this->createOrderItem($order, $item, $sourceOrder, $price, $product);
            }
        } catch (Exception $exception) {
            Log::error('Failed to create order items', [
                'order_id' => $order->id ?? 'N/A',
                'source_order_id' => $sourceOrder->i_id,
                'error' => $exception->getMessage(),
            ]);

            $output->newLine(2);
            $fileName = Str::of($exception->getFile())->classBasename();
            $components->error(sprintf('Failed to create order item: %s in %s on Line %d.', $exception->getMessage(), $fileName, $exception->getLine()));
        }

        return $orderItems;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function createOrderItem(Order $order, array $item, object $sourceOrder, ?Price $price, ?Product $product): OrderItem
    {
        $orderItem = new OrderItem;
        $orderItem->forceFill([
            'order_id' => $order->id,
            'price_id' => $price?->id,
            'name' => Str::trim($item['itemName'] ?? $product?->name ?? 'Order #'.$sourceOrder->i_id),
            'amount' => (float) ($item['cost'] ?? 0),
            'quantity' => $item['quantity'] ?? 1,
            'external_item_id' => $item['itemID'] ?? null,
        ]);

        return $orderItem;
    }

    protected function findUser(object $sourceOrder): ?User
    {
        $mappedUserId = UserImporter::getUserMapping((int) $sourceOrder->i_member);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        return null;
    }

    protected function findProductByItemType(object $sourceOrder, string $itemType, int $itemId, string $itemAction): ?Product
    {
        if ($itemType === 'package') {
            $mappedProductId = ProductImporter::getProductMapping($itemId);

            if ($mappedProductId !== null && $mappedProductId !== 0) {
                return Product::query()->find($mappedProductId);
            }

            return null;
        }

        if ($itemType === 'subscription') {
            if ($itemAction === 'new') {
                $mappedProductId = SubscriptionImporter::getSubscriptionMapping($itemId);

                if ($mappedProductId !== null && $mappedProductId !== 0) {
                    return Product::query()->find($mappedProductId);
                }

                return null;
            }

            if ($itemAction === 'renewal') {
                $renewalIds = array_filter(explode(',', (string) $sourceOrder->i_renewal_ids));

                if ($renewalIds === []) {
                    return null;
                }

                $renewalId = $renewalIds[0];

                if (! $sourcePurchase = DB::connection($this->source->getConnection())->table('nexus_purchases')->where('ps_id', $renewalId)->first()) {
                    return null;
                }

                if ($sourcePurchase->ps_item_id === null || $sourcePurchase->ps_item_id === 0) {
                    return null;
                }

                $mappedProductId = SubscriptionImporter::getSubscriptionMapping($sourcePurchase->ps_item_id);

                if ($mappedProductId !== null && $mappedProductId !== 0) {
                    return Product::query()->find($mappedProductId);
                }

                return null;
            }
        }

        return null;
    }

    protected function findPriceForProduct(Product $product, float $amount, string $currency): ?Price
    {
        $price = Price::query()
            ->whereBelongsTo($product)
            ->where('currency', strtoupper($currency))
            ->where('amount', $amount)
            ->active()
            ->first();

        if ($price instanceof Price) {
            return $price;
        }

        return Price::query()
            ->whereBelongsTo($product)
            ->active()
            ->default()
            ->first();
    }

    protected function cacheOrderMapping(int $sourceOrderId, int $targetOrderId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceOrderId, $targetOrderId, self::CACHE_TTL);
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();
        $config = $this->getConfig();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->orderBy('i_id')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('i_member', $config->userId));
    }
}
