<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Data\SubscriptionData;
use App\Enums\OrderStatus;
use App\Enums\PriceType;
use App\Enums\ProductType;
use App\Enums\ProrationBehavior;
use App\Enums\SubscriptionInterval;
use App\Jobs\Store\ImportSubscription;
use App\Managers\PaymentManager;
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
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserSubscriptionImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'user_subscriptions';

    public const string CACHE_KEY_PREFIX = 'migration:ic:user_subscription_map:';

    public const string CACHE_TAG = 'migration:ic:user_subscriptions';

    protected ?PaymentManager $paymentManager = null;

    public function __construct(MigrationSource $source)
    {
        parent::__construct($source);

        $this->paymentManager = app(PaymentManager::class);
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
        return 'nexus_member_subscriptions';
    }

    /**
     * @return ImporterDependency[]
     */
    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'User subscriptions require users to exist'),
            ImporterDependency::requiredPre('orders', 'User subscriptions require orders to exist'),
            ImporterDependency::requiredPre('subscriptions', 'User subscriptions require subscription packages to exist'),
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

        $totalUserSubscriptions = $baseQuery->clone()->countOffset();

        if ($output->isVerbose()) {
            $components->info(sprintf('Found %s user subscriptions to migrate...', $totalUserSubscriptions));
        }

        $progressBar = $output->createProgressBar($totalUserSubscriptions);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($userSubscriptions) use ($config, $result, $progressBar, $output, $components, &$processed): bool {
            foreach ($userSubscriptions as $sourceUserSubscription) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importUserSubscription($sourceUserSubscription, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceUserSubscription->sub_id ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import user subscription', [
                        'source_id' => $sourceUserSubscription->sub_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import user subscription: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
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

    protected function importUserSubscription(object $sourceUserSubscription, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $user = $this->findUser($sourceUserSubscription);

        if (! $user instanceof User) {
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceUserSubscription->sub_id,
                    'purchase_id' => 'N/A',
                    'invoice_id' => 'N/A',
                    'name' => 'N/A',
                    'reason' => 'User not found',
                ]);
            }

            return;
        }

        $this->setStripeCustomerId($user, $sourceUserSubscription->sub_member_id, $config);

        $price = $this->findPrice($sourceUserSubscription);

        if (! $price instanceof Price) {
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceUserSubscription->sub_id,
                    'purchase_id' => $sourceUserSubscription->sub_purchase_id,
                    'invoice_id' => $sourceUserSubscription->sub_invoice_id,
                    'name' => $user->name,
                    'reason' => 'Price not found',
                ]);
            }

            return;
        }

        $order = null;

        if (! $config->isDryRun) {
            $backdateStartDate = $this->getStartDate($sourceUserSubscription);
            $billingCycleAnchor = $this->getExpirationDate($sourceUserSubscription);

            if (is_null($billingCycleAnchor)) {
                $this->createOrderForNonExpiringSubscription($user);

                $result->incrementSkipped(self::ENTITY_NAME);

                if ($output->isVerbose()) {
                    $result->recordSkipped(self::ENTITY_NAME, [
                        'source_id' => $sourceUserSubscription->sub_id,
                        'purchase_id' => $sourceUserSubscription->sub_purchase_id,
                        'invoice_id' => $sourceUserSubscription->sub_invoice_id,
                        'name' => $user->name,
                        'reason' => 'Created non-expiring subscription product',
                    ]);
                }

                return;
            }

            if ($billingCycleAnchor->isPast()) {
                $result->incrementSkipped(self::ENTITY_NAME);

                if ($output->isVerbose()) {
                    $result->recordSkipped(self::ENTITY_NAME, [
                        'source_id' => $sourceUserSubscription->sub_id,
                        'purchase_id' => $sourceUserSubscription->sub_purchase_id,
                        'invoice_id' => $sourceUserSubscription->sub_invoice_id,
                        'name' => $user->name,
                        'reason' => 'Expiration date does not exist or is in the past',
                    ]);
                }

                return;
            }

            if ($this->paymentManager->currentSubscription($user) instanceof SubscriptionData) {
                $result->incrementSkipped(self::ENTITY_NAME);

                if ($output->isVerbose()) {
                    $result->recordSkipped(self::ENTITY_NAME, [
                        'source_id' => $sourceUserSubscription->sub_id,
                        'purchase_id' => $sourceUserSubscription->sub_purchase_id,
                        'invoice_id' => $sourceUserSubscription->sub_invoice_id,
                        'name' => $user->name,
                        'reason' => 'User already has current subscription',
                    ]);
                }

                return;
            }

            $order = $this->createOrder($user, $price);

            ImportSubscription::dispatch($order, ProrationBehavior::None, $backdateStartDate, $billingCycleAnchor);
        }

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourceUserSubscription->sub_id,
                'user_id' => $user->id,
                'order_id' => $order?->id,
                'name' => $user->name,
            ]);
        }
    }

    protected function findUser(object $sourceUserSubscription): ?User
    {
        $mappedUserId = UserImporter::getUserMapping((int) $sourceUserSubscription->sub_member_id);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        return null;
    }

    protected function findPrice(object $sourceUserSubscription): ?Price
    {
        if (! isset($sourceUserSubscription->sub_purchase_id) || $sourceUserSubscription->sub_purchase_id === 0) {
            return null;
        }

        $sourcePurchase = DB::connection($this->source->getConnection())
            ->table('nexus_purchases')
            ->where('ps_id', $sourceUserSubscription->sub_purchase_id)
            ->first();

        if ($sourcePurchase === null || $sourcePurchase->ps_type !== 'subscription' || ! isset($sourcePurchase->ps_item_id) || $sourcePurchase->ps_item_id === 0) {
            return null;
        }

        $mappedProductId = SubscriptionImporter::getSubscriptionMapping((int) $sourcePurchase->ps_item_id);

        if ($mappedProductId === null || $mappedProductId === 0) {
            return null;
        }

        $product = Product::query()->with('prices')->find($mappedProductId);

        if (! $product instanceof Product) {
            return null;
        }

        $interval = match ($sourcePurchase->ps_renewal_unit) {
            'y' => SubscriptionInterval::Yearly,
            default => SubscriptionInterval::Monthly,
        };

        return $product->prices->firstWhere('interval', $interval);
    }

    protected function createOrderForNonExpiringSubscription(User $user): Order
    {
        $product = Product::firstOrCreate([
            'name' => 'Non Expiring Subscription',
            'type' => ProductType::Product,
        ]);

        $price = $product->prices()->create([
            'name' => 'One-Time',
            'type' => PriceType::OneTime,
            'amount' => 0,
            'currency' => 'USD',
            'interval_count' => 1,
            'is_active' => 1,
            'is_default' => 1,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'status' => OrderStatus::Succeeded,
            'amount_due' => 0,
            'amount_overpaid' => 0,
            'amount_remaining' => 0,
            'amount_paid' => 0,
        ]);

        $order->items()->create([
            'name' => $product->name,
            'description' => '',
            'price_id' => $price->id,
            'amount' => 0,
            'quantity' => 1,
        ]);

        return $order;
    }

    protected function createOrder(User $user, Price $price): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'status' => OrderStatus::Succeeded,
            'amount_due' => 0,
            'amount_overpaid' => 0,
            'amount_remaining' => 0,
            'amount_paid' => 0,
        ]);

        $order->items()->create([
            'name' => $price->product->name.' - Website Migration',
            'description' => 'This order was created automatically to transfer your subscription to a new platform. Your renewal schedule will remain the same. If you do not have a payment method on file, an invoice will be generated for you at the next regularly scheduled renewal date.',
            'price_id' => $price->id,
            'amount' => 0,
            'quantity' => 1,
        ]);

        return $order;
    }

    protected function getStartDate(object $sourceUserSubscription): ?CarbonInterface
    {
        if (isset($sourceUserSubscription->sub_purchase_id)) {
            $sourcePurchase = DB::connection($this->source->getConnection())
                ->table('nexus_purchases')
                ->where('ps_id', $sourceUserSubscription->sub_purchase_id)
                ->first();

            if ($sourcePurchase && isset($sourcePurchase->ps_start) && is_numeric($sourcePurchase->ps_start)) {
                return Carbon::parse($sourcePurchase->ps_start);
            }
        }

        return isset($sourceUserSubscription->ps_start)
            ? Carbon::parse($sourceUserSubscription->ps_start)
            : null;
    }

    protected function getExpirationDate(object $sourceUserSubscription): ?CarbonInterface
    {
        if (isset($sourceUserSubscription->sub_purchase_id)) {
            $sourcePurchase = DB::connection($this->source->getConnection())
                ->table('nexus_purchases')
                ->where('ps_id', $sourceUserSubscription->sub_purchase_id)
                ->first();

            if ($sourcePurchase->ps_expire === 0) {
                return null;
            }

            if ($sourcePurchase && isset($sourcePurchase->ps_expire) && is_numeric($sourcePurchase->ps_expire)) {
                return Carbon::parse($sourcePurchase->ps_expire);
            }
        }

        return isset($sourceUserSubscription->sub_expire) && $sourceUserSubscription->sub_expire !== 0
            ? Carbon::parse($sourceUserSubscription->sub_expire)
            : null;
    }

    protected function setStripeCustomerId(User $user, int $memberId, MigrationConfig $config): void
    {
        if ($user->hasStripeId()) {
            return;
        }

        $stripeId = DB::connection($this->source->getConnection())
            ->table('stripe_customers')
            ->where('member_id', $memberId)
            ->value('stripe_id');

        if ($stripeId && ! $config->isDryRun) {
            $user->updateQuietly([
                'stripe_id' => $stripeId,
            ]);
        }
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();
        $config = $this->getConfig();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->whereNotNull('sub_member_id')
            ->whereNotNull('sub_package_id')
            ->where('sub_active', 1)
            ->where('sub_cancelled', 0)
            ->orderBy('sub_id')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('sub_member_id', $config->userId));
    }
}
