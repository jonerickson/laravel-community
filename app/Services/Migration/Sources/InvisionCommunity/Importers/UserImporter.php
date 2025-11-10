<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\User;
use App\Services\Migration\AbstractImporter;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationConfig;
use App\Services\Migration\MigrationResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'users';

    public const string CACHE_KEY_PREFIX = 'migration:ic:user_map:';

    public const string CACHE_TAG = 'migration:ic:users';

    public static function getUserMapping(int $sourceUserId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceUserId);
    }

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getSourceTable(): string
    {
        return 'core_members';
    }

    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('groups', 'User accounts require groups to exist for proper role assignment'),
        ];
    }

    public function getTotalRecordsCount(): int
    {
        return $this->getBaseQuery()->count();
    }

    public function import(
        MigrationResult $result,
        OutputStyle $output,
    ): void {
        $config = $this->getConfig();

        $baseQuery = $this->getBaseQuery()
            ->when($config->offset !== null && $config->offset !== 0, fn (Builder $builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn (Builder $builder) => $builder->limit($config->limit));

        $totalUsers = $baseQuery->clone()->countOffset();

        $output->writeln("Found $totalUsers users to migrate...");

        $progressBar = $output->createProgressBar($totalUsers);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($users) use ($config, $result, $progressBar, $output, &$processed): bool {
            foreach ($users as $sourceUser) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importUser($sourceUser, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVeryVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceUser->member_id ?? 'unknown',
                            'email' => $sourceUser->email ?? 'unknown',
                            'name' => $sourceUser->name ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import user', [
                        'source_id' => $sourceUser->member_id ?? 'unknown',
                        'email' => $sourceUser->email ?? 'unknown',
                        'name' => $sourceUser->name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $output->writeln("<error>Failed to import user {$sourceUser->email}: {$e->getMessage()}</error>");
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();

        $output->newLine();
        $output->writeln("Migrated $processed users...");
        $output->newLine();

        if (! $config->isDryRun) {
            $output->writeln('Syncing billing addresses...');
            $this->syncBillingAddresses($config, $output);
            $output->writeln('Billing addresses synced.');
            $output->newLine();
        }
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

    protected function importUser(object $sourceUser, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $email = $sourceUser->email;

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser) {
            $this->cacheUserMapping($sourceUser->member_id, $existingUser->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVeryVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceUser->member_id,
                    'email' => $email,
                    'name' => $sourceUser->name,
                    'reason' => 'Already exists',
                ]);
            }

            return;
        }

        $user = new User;
        $user->forceFill([
            'name' => $sourceUser->name,
            'email' => $email,
            'email_verified_at' => $this->isEmailValidated($sourceUser) ? Carbon::now() : null,
            'password' => $this->migratePassword($sourceUser),
            'signature' => strip_tags($sourceUser->signature ?? ''),
            'last_seen_at' => $sourceUser->last_activity ? Carbon::createFromTimestamp($sourceUser->last_activity) : null,
            'created_at' => Carbon::createFromTimestamp($sourceUser->joined),
        ]);

        if (! $config->isDryRun) {
            $user->save();
            $this->assignGroups($user, $sourceUser);
            $this->cacheUserMapping($sourceUser->member_id, $user->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourceUser->member_id,
                'target_id' => $user->id ?? 'N/A (dry run)',
                'email' => $user->email,
                'name' => $user->name,
                'created_at' => $user->created_at?->toDateTimeString() ?? 'N/A',
            ]);
        }
    }

    protected function assignGroups(User $user, object $sourceUser): void
    {
        $groupIds = [];

        if (! empty($sourceUser->member_group_id)) {
            $mappedGroupId = GroupImporter::getGroupMapping((int) $sourceUser->member_group_id);

            if ($mappedGroupId !== null && $mappedGroupId !== 0) {
                $groupIds[] = $mappedGroupId;
            }
        }

        if (! empty($sourceUser->mgroup_others)) {
            foreach (explode(',', (string) $sourceUser->mgroup_others) as $secondaryGroupId) {
                $mappedGroupId = GroupImporter::getGroupMapping((int) $secondaryGroupId);

                if ($mappedGroupId && ! in_array($mappedGroupId, $groupIds)) {
                    $groupIds[] = $mappedGroupId;
                }
            }
        }

        if ($groupIds !== []) {
            $user->groups()->sync(array_unique(array_values($groupIds)));
        }
    }

    protected function migratePassword(object $sourceUser): ?string
    {
        if (empty($sourceUser->members_pass_hash)) {
            return null;
        }

        return Hash::make(Str::random(32));
    }

    protected function isEmailValidated(object $sourceUser): bool
    {
        $validatedBit = 65536;

        return (bool) ((int) ($sourceUser->members_bitoptions ?? 0) & $validatedBit);
    }

    protected function cacheUserMapping(int $sourceUserId, int $targetUserId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceUserId, $targetUserId, 60 * 60 * 24 * 7);
    }

    protected function syncBillingAddresses(MigrationConfig $config, OutputStyle $output): void
    {
        $connection = $this->source->getConnection();

        $addresses = DB::connection($connection)
            ->table('nexus_customer_addresses')
            ->whereNotNull('address')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('member', $config->userId))
            ->get();

        $progressBar = $output->createProgressBar($addresses->count());
        $progressBar->start();

        foreach ($addresses as $sourceAddress) {
            try {
                $targetUserId = self::getUserMapping((int) $sourceAddress->member);
                if ($targetUserId === null) {
                    continue;
                }
                if ($targetUserId === 0) {
                    continue;
                }

                $user = User::query()->find($targetUserId);

                if (! $user) {
                    continue;
                }

                $addressData = json_decode((string) $sourceAddress->address, true);

                if (! $addressData) {
                    continue;
                }

                $user->update([
                    'billing_address' => $addressData['addressLines'][0] ?? null,
                    'billing_address_line_2' => $addressData['addressLines'][1] ?? null,
                    'billing_city' => $addressData['city'] ?? null,
                    'billing_state' => $addressData['region'] ?? null,
                    'billing_postal_code' => $addressData['postalCode'] ?? null,
                    'billing_country' => $addressData['country'] ?? null,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to sync billing address', [
                    'source_member_id' => $sourceAddress->member ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->newLine();
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();
        $config = $this->getConfig();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->orderBy('member_id')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('member_id', $config->userId));
    }
}
