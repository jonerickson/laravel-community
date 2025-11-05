<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\Role;
use App\Jobs\ImportSubscription;
use App\Models\Order;
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
            ->whereNotNull('sub_member_id')
            ->where('sub_member_id', 1)
            ->whereNotNull('sub_package_id')
            ->orderBy('sub_id')
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->offset($offset))
            ->when($limit !== null && $limit !== 0, fn ($builder) => $builder->limit($limit));

        $totalUserSubscriptions = $baseQuery->count();

        $output->writeln("Found {$totalUserSubscriptions} user subscriptions to migrate...");

        $progressBar = $output->createProgressBar($totalUserSubscriptions);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($batchSize, function ($userSubscriptions) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): bool {
            foreach ($userSubscriptions as $sourceUserSubscription) {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return false;
                }

                try {
                    $this->importUserSubscription($sourceUserSubscription, $isDryRun, $result);
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
            if ($sourceUserSubscription->sub_added_manually && ! $sourceUserSubscription->sub_renews) {
                return;
            }

            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceUserSubscription->sub_id,
                'reason' => 'Order not found from imported orders',
            ]);

            return;
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
            $anchorBillingCycle = isset($sourceUserSubscription->sub_expires) ? Carbon::parse($sourceUserSubscription->sub_expires) : null;
            ImportSubscription::dispatch($order, $anchorBillingCycle);
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
