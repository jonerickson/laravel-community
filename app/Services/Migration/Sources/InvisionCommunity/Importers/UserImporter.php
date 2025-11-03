<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\User;
use App\Services\Migration\AbstractImporter;
use App\Services\Migration\ImporterDependency;
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
    protected const string ENTITY_NAME = 'users';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:user_map:';

    protected const string CACHE_TAG = 'migration:ic:users';

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
            ->orderBy('member_id')
            ->when($offset !== null && $offset !== 0, fn (Builder $builder) => $builder->offset($offset))
            ->when($limit !== null && $limit !== 0, fn (Builder $builder) => $builder->limit($limit));

        if ($offset > 0) {
            $totalUsers = DB::connection($connection)
                ->table(DB::raw("({$baseQuery->toSql()}) as limited"))
                ->mergeBindings($baseQuery)
                ->count();
        } else {
            $totalUsers = $baseQuery->count();
        }

        $output->writeln("Found $totalUsers users to migrate...");

        $progressBar = $output->createProgressBar($totalUsers);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($batchSize, function ($users) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): bool {
            foreach ($users as $sourceUser) {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return false;
                }

                try {
                    $this->importUser($sourceUser, $isDryRun, $result, $output);
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
        $output->newLine(2);
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

    protected function importUser(object $sourceUser, bool $isDryRun, MigrationResult $result, OutputStyle $output): void
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

        if (! $isDryRun) {
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
            $secondaryGroupIds = explode(',', (string) $sourceUser->mgroup_others);

            foreach ($secondaryGroupIds as $secondaryGroupId) {
                $mappedGroupId = GroupImporter::getGroupMapping((int) $secondaryGroupId);

                if ($mappedGroupId && ! in_array($mappedGroupId, $groupIds)) {
                    $groupIds[] = $mappedGroupId;
                }
            }
        }

        if ($groupIds !== []) {
            $user->groups()->sync($groupIds);
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
        Cache::put(self::CACHE_KEY_PREFIX.$sourceUserId, $targetUserId, 60 * 60 * 24 * 7);
    }
}
