<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\OrderStatus;
use App\Enums\PriceType;
use App\Enums\ProrationBehavior;
use App\Enums\Role;
use App\Enums\SubscriptionInterval;
use App\Jobs\ImportSubscription;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use App\Services\Migration\AbstractImporter;
use App\Services\Migration\Contracts\MigrationSource;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationConfig;
use App\Services\Migration\MigrationResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserSubscriptionImporter extends AbstractImporter
{
    protected const string ENTITY_NAME = 'user_subscriptions';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:user_subscription_map:';

    protected const string CACHE_TAG = 'migration:ic:user_subscriptions';

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
        return 'nexus_member_subscriptions';
    }

    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'User subscriptions require users to exist'),
            ImporterDependency::requiredPre('orders', 'User subscriptions require orders to exist'),
            ImporterDependency::requiredPre('subscriptions', 'User subscriptions require subscription packages to exist'),
        ];
    }

    public function import(
        MigrationSource $source,
        MigrationConfig $config,
        MigrationResult $result,
        OutputStyle $output,
    ): void {
        $connection = $source->getConnection();

        $baseQuery = DB::connection($connection)
            ->table($this->getSourceTable())
            ->whereNotNull('sub_member_id')
            ->whereNotNull('sub_package_id')
            ->orderBy('sub_id')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('sub_member_id', $config->userId))
            ->when($config->offset !== null && $config->offset !== 0, fn ($builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn ($builder) => $builder->limit($config->limit));

        $totalUserSubscriptions = $baseQuery->count();

        $output->writeln("Found {$totalUserSubscriptions} user subscriptions to migrate...");

        $progressBar = $output->createProgressBar($totalUserSubscriptions);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($userSubscriptions) use ($config, $result, $progressBar, $output, &$processed): bool {
            foreach ($userSubscriptions as $sourceUserSubscription) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importUserSubscription($sourceUserSubscription, $config->isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);
                    $result->recordFailed(self::ENTITY_NAME, [
                        'source_id' => $sourceUserSubscription->sub_id ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import user subscription', [
                        'source_id' => $sourceUserSubscription->sub_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import user subscription: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();

        $output->newLine();
        $output->writeln("Migrated $processed user subscriptions...");
        $output->newLine();
    }

    protected function importUserSubscription(object $sourceUserSubscription, bool $isDryRun, MigrationResult $result): void
    {
        $user = $this->findUser($sourceUserSubscription);

        if (! $user instanceof User) {
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceUserSubscription->sub_id,
                'reason' => 'User not found',
            ]);

            return;
        }

        $this->setStripeCustomerId($user, $sourceUserSubscription->sub_member_id, $isDryRun);

        $orderId = OrderImporter::getOrderMapping((int) $sourceUserSubscription->sub_invoice_id);

        if ($orderId === null || $orderId === 0) {
            if ($sourceUserSubscription->sub_added_manually) {
                $order = $user->orders()->create([
                    'status' => OrderStatus::Succeeded,
                    'amount_due' => 0,
                    'amount_overpaid' => 0,
                    'amount_paid' => 0,
                    'amount_remaining' => 0,
                ]);

                $product = $this->findProduct($sourceUserSubscription);

                if (! $product instanceof Product) {
                    $result->incrementSkipped(self::ENTITY_NAME);
                    $result->recordSkipped(self::ENTITY_NAME, [
                        'source_id' => $sourceUserSubscription->sub_id,
                        'reason' => 'Product not found to generate order',
                    ]);

                    return;
                }

                $order->items()->create([
                    'name' => $product ? $product->name : 'Unknown Product',
                    'amount' => 0,
                    'quantity' => 1,
                    'price_id' => Price::firstOrCreate([
                        'type' => PriceType::Recurring,
                        'amount' => 0,
                        'product_id' => $product->id,
                    ], [
                        'reference_id' => Str::uuid()->toString(),
                        'name' => 'One-Time',
                        'currency' => 'USD',
                        'interval' => SubscriptionInterval::Yearly,
                        'interval_count' => 1,
                        'is_default' => false,
                        'is_active' => true,
                    ])->getKey(),
                ]);

                $orderId = $order->getKey();
            } else {
                $result->incrementSkipped(self::ENTITY_NAME);
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceUserSubscription->sub_id,
                    'reason' => 'Order not found from imported orders',
                ]);

                return;
            }
        }

        $order = Order::query()->find($orderId);

        if (! $order instanceof Order) {
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceUserSubscription->sub_id,
                'reason' => 'Order not found',
            ]);

            return;
        }

        if (! $isDryRun) {
            $backdateStartDate = isset($sourceUserSubscription->sub_start) ? Carbon::parse($sourceUserSubscription->sub_start) : null;
            $billingCycleAnchor = isset($sourceUserSubscription->sub_expires) ? Carbon::parse($sourceUserSubscription->sub_expires) : null;

            ImportSubscription::dispatch($order, ProrationBehavior::None, $backdateStartDate, $billingCycleAnchor);
        }

        $result->incrementMigrated(self::ENTITY_NAME);
        $result->recordMigrated(self::ENTITY_NAME, [
            'source_id' => $sourceUserSubscription->sub_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'order_id' => $order->id,
        ]);
    }

    protected function findUser(object $sourceUserSubscription): ?User
    {
        $mappedUserId = UserImporter::getUserMapping((int) $sourceUserSubscription->sub_member_id);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        if ($adminUser = User::query()->role(Role::Administrator)->oldest()->first()) {
            return $adminUser;
        }

        return null;
    }

    protected function findProduct(object $sourceUserSubscription): ?Product
    {
        $mappedProductId = SubscriptionImporter::getSubscriptionMapping($sourceUserSubscription->sub_package_id);

        if ($mappedProductId !== null && $mappedProductId !== 0) {
            return Product::query()->find($mappedProductId);
        }

        return null;
    }

    protected function setStripeCustomerId(User $user, int $memberId, bool $isDryRun): void
    {
        if ($user->hasStripeId()) {
            return;
        }

        $stripeId = DB::connection($this->source->getConnection())
            ->table('stripe_customers')
            ->where('member_id', $memberId)
            ->value('stripe_id');

        if ($stripeId && ! $isDryRun) {
            $user->updateQuietly([
                'stripe_id' => $stripeId,
            ]);
        }
    }
}
