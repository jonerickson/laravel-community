<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Data\SubscriptionData;
use App\Enums\ProrationBehavior;
use App\Enums\Role;
use App\Jobs\ImportSubscription;
use App\Managers\PaymentManager;
use App\Models\Order;
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
                    'reason' => 'User not found',
                ]);
            }

            return;
        }

        $this->setStripeCustomerId($user, $sourceUserSubscription->sub_member_id, $config);

        $orderId = OrderImporter::getOrderMapping((int) $sourceUserSubscription->sub_invoice_id);

        if ($orderId === null || $orderId === 0) {
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceUserSubscription->sub_id,
                    'purchase_id' => $sourceUserSubscription->sub_purchase_id,
                    'invoice_id' => $sourceUserSubscription->sub_invoice_id,
                    'reason' => 'Order not found from imported orders',
                ]);
            }

            return;
        }

        $order = Order::query()->find($orderId);

        if (! $order instanceof Order) {
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceUserSubscription->sub_id,
                    'reason' => 'Order not found',
                ]);
            }

            return;
        }

        if (! $config->isDryRun) {
            $backdateStartDate = isset($sourceUserSubscription->sub_start)
                ? Carbon::parse($sourceUserSubscription->sub_start)
                : now();

            $billingCycleAnchor = $this->getExpirationDate($sourceUserSubscription);

            if (is_null($billingCycleAnchor) || ($billingCycleAnchor instanceof CarbonInterface && $billingCycleAnchor->isPast())) {
                $result->incrementSkipped(self::ENTITY_NAME);

                if ($output->isVerbose()) {
                    $result->recordSkipped(self::ENTITY_NAME, [
                        'source_id' => $sourceUserSubscription->sub_id,
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
                        'reason' => 'User already has current subscription',
                    ]);
                }

                return;
            }

            ImportSubscription::dispatch($order, ProrationBehavior::None, $backdateStartDate, $billingCycleAnchor);
        }

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourceUserSubscription->sub_id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'order_id' => $order->id,
            ]);
        }
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

    protected function getExpirationDate(object $sourceUserSubscription): ?CarbonInterface
    {
        $expirationDate = $sourceUserSubscription->sub_expire;

        if (isset($sourceUserSubscription->sub_purchase_id) && isset($expirationDate) && is_numeric($expirationDate)) {
            $sourcePurchase = DB::connection($this->source->getConnection())->table('nexus_purchases')->where('ps_id', $sourceUserSubscription->sub_purchase_id)->first();

            if ($sourcePurchase && isset($sourcePurchase->ps_expire) && is_numeric($sourcePurchase->ps_expire)) {
                return Carbon::parse($sourcePurchase->ps_expire);
            }
        }

        return isset($expirationDate) ? Carbon::parse($expirationDate) : null;
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
            ->where('sub_renews', 1)
            ->where('sub_active', 1)
            ->where('sub_cancelled', 0)
            ->orderBy('sub_id')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('sub_member_id', $config->userId));
    }
}
