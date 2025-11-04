<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use App\Services\Migration\AbstractImporter;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderImporter extends AbstractImporter
{
    protected const string ENTITY_NAME = 'orders';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:order_map:';

    protected const string CACHE_TAG = 'migration:ic:orders';

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

    public function getDependencies(): array
    {
        return [
            // ImporterDependency::requiredPre('users', 'Orders require users to exist for customer assignment'),
            // ImporterDependency::requiredPre('products', 'Orders require products to exist for order items'),
            // ImporterDependency::optionalPre('subscriptions', 'Orders may be linked to a subscription plan'),
        ];
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

        $baseQuery = DB::connection($connection)
            ->table($this->getSourceTable())
            ->orderBy('i_id')
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->offset($offset))
            ->when($limit !== null && $limit !== 0, fn ($builder) => $builder->limit($limit));

        $totalOrders = $baseQuery->count();

        $output->writeln("Found {$totalOrders} paid orders to migrate...");

        $progressBar = $output->createProgressBar($totalOrders);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($batchSize, function ($orders) use ($connection, $limit, $isDryRun, $result, $progressBar, $output, &$processed): bool {
            foreach ($orders as $sourceOrder) {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return false;
                }

                try {
                    $this->importOrder($connection, $sourceOrder, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);
                    $result->recordFailed(self::ENTITY_NAME, [
                        'source_id' => $sourceOrder->i_id ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import order', [
                        'source_id' => $sourceOrder->i_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import order: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();

        $output->newLine();
        $output->writeln("Migrated $processed orders...");
        $output->newLine();
    }

    protected function importOrder(string $connection, object $sourceOrder, bool $isDryRun, MigrationResult $result): void
    {
        $user = $this->findUser($sourceOrder);

        if (! $user instanceof User) {
            $result->incrementFailed(self::ENTITY_NAME);
            $result->recordFailed(self::ENTITY_NAME, [
                'source_id' => $sourceOrder->i_id,
                'error' => 'Could not find user',
            ]);

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
            'invoice_number' => $sourceOrder->i_id ?: null,
            'external_order_id' => 'ic_'.$sourceOrder->i_id,
            'external_invoice_id' => 'ic_'.$sourceOrder->i_id,
            'created_at' => $sourceOrder->i_date
                ? Carbon::createFromTimestamp($sourceOrder->i_date)
                : Carbon::now(),
            'updated_at' => $sourceOrder->i_paid
                ? Carbon::createFromTimestamp($sourceOrder->i_paid)
                : ($sourceOrder->i_date
                    ? Carbon::createFromTimestamp($sourceOrder->i_date)
                    : Carbon::now()),
        ]);

        if (! $isDryRun) {
            $order->save();
            $this->cacheOrderMapping($sourceOrder->i_id, $order->id);
        }

        $orderItems = $this->createOrderItems($connection, $sourceOrder, $order, $isDryRun, $result);

        if (! $isDryRun) {
            /** @var OrderItem $orderItem */
            foreach ($orderItems as $orderItem) {
                $orderItem->save();

                $result->incrementMigrated('order_items');
                $result->recordMigrated('order_items', [
                    'order_id' => $order->id,
                    'price_id' => $orderItem->price_id,
                    'product_name' => $orderItem->name,
                    'amount' => $orderItem->amount,
                ]);
            }
        }

        $orderItemsSummary = collect($orderItems)->map(fn (OrderItem $item): string => ($item->name ?? 'Unknown').' - '.$item->amount)->implode(', ');

        $result->incrementMigrated(self::ENTITY_NAME);
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

    protected function getOrderStatus(object $sourceOrder): OrderStatus
    {
        return match ($sourceOrder->i_status) {
            'paid' => OrderStatus::Succeeded,
            'canc' => OrderStatus::Cancelled,
            'expd' => OrderStatus::Expired,
            default => OrderStatus::Pending,
        };
    }

    protected function createOrderItems(string $connection, object $sourceOrder, Order $order, bool $isDryRun, MigrationResult $result): array
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

                if (! $itemId || ! $itemType) {
                    $orderItems[] = $this->createOrderItem($order, $item, $sourceOrder, null, null);

                    continue;
                }

                $product = $this->findProductByItemType($itemType, $itemId);

                if (! $product instanceof Product) {
                    $orderItems[] = $this->createOrderItem($order, $item, $sourceOrder, null, null);

                    continue;
                }

                $amount = (float) ($item['cost'] ?? 0);
                $price = $this->findPriceForProduct($product, $amount, $sourceOrder->i_currency);

                $orderItems[] = $this->createOrderItem($order, $item, $sourceOrder, $price, $product);
            }
        } catch (Exception $e) {
            Log::error('Failed to create order items', [
                'order_id' => $order->id ?? 'N/A',
                'source_order_id' => $sourceOrder->i_id,
                'error' => $e->getMessage(),
            ]);

            if (! $isDryRun) {
                $result->incrementFailed('order_items');
                $result->recordFailed('order_items', [
                    'order_id' => $order->id ?? 'N/A',
                    'source_order_id' => $sourceOrder->i_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $orderItems;
    }

    protected function createOrderItem(Order $order, array $item, object $sourceOrder, ?Price $price, ?Product $product): OrderItem
    {
        $orderItem = new OrderItem;
        $orderItem->forceFill([
            'order_id' => $order->id,
            'price_id' => $price?->id,
            'name' => $item['itemName'] ?? $product?->name ?? 'Order #'.$sourceOrder->i_id,
            'amount' => (float) ($item['cost'] ?? 0),
            'quantity' => $item['quantity'] ?? 1,
            'commission_amount' => 0,
        ]);

        return $orderItem;
    }

    protected function findUser(object $sourceOrder): ?User
    {
        $mappedUserId = UserImporter::getUserMapping((int) $sourceOrder->i_member);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        if ($adminUser = User::query()->role(Role::Administrator)->oldest()->first()) {
            return $adminUser;
        }

        return null;
    }

    protected function findProductByItemType(string $itemType, int $itemId): ?Product
    {
        if ($itemType === 'package') {
            $mappedProductId = ProductImporter::getProductMapping($itemId);

            if ($mappedProductId !== null && $mappedProductId !== 0) {
                return Product::query()->find($mappedProductId);
            }

            return null;
        }

        if ($itemType === 'subscription') {
            $mappedProductId = SubscriptionImporter::getSubscriptionMapping($itemId);

            if ($mappedProductId !== null && $mappedProductId !== 0) {
                return Product::query()->find($mappedProductId);
            }

            return null;
        }

        return null;
    }

    protected function findPriceForProduct(Product $product, float $amount, string $currency): ?Price
    {
        $price = Price::query()
            ->where('product_id', $product->id)
            ->where('currency', strtoupper($currency))
            ->where('amount', $amount)
            ->where('is_active', true)
            ->first();

        if ($price instanceof Price) {
            return $price;
        }

        return Price::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    protected function cacheOrderMapping(int $sourceOrderId, int $targetOrderId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceOrderId, $targetOrderId, self::CACHE_TTL);
    }
}
