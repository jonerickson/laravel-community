<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\PostType;
use App\Enums\Role;
use App\Models\Post;
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

class BlogImporter extends AbstractImporter
{
    protected const string ENTITY_NAME = 'blogs';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:blog_map:';

    protected const string CACHE_TAG = 'migration:ic:blog';

    public static function getBlogMapping(int $sourceBlogId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceBlogId);
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
        return 'blog_entries';
    }

    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'Blog posts require users to exist for author assignment'),
            ImporterDependency::optionalPost('blog_comments', 'Import blog post comments'),
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
        $baseQuery = DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('entry_status', 'published')
            ->orderBy('entry_id')
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->offset($offset))
            ->when($limit !== null && $limit !== 0, fn ($builder) => $builder->limit($limit));

        $totalBlogs = $baseQuery->count();

        $output->writeln("Found {$totalBlogs} blog entries to migrate...");

        $progressBar = $output->createProgressBar($totalBlogs);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($batchSize, function ($blogEntries) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): bool {
            foreach ($blogEntries as $sourceBlogEntry) {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return false;
                }

                try {
                    $this->importBlogEntry($sourceBlogEntry, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVeryVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceBlogEntry->entry_id ?? 'unknown',
                            'title' => $sourceBlogEntry->entry_name ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import blog entry', [
                        'source_id' => $sourceBlogEntry->entry_id ?? 'unknown',
                        'title' => $sourceBlogEntry->entry_name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import blog entry: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();

        $output->newLine();
        $output->writeln("Migrated $processed blog entries...");
        $output->newLine();
    }

    protected function importBlogEntry(object $sourceBlogEntry, bool $isDryRun, MigrationResult $result): void
    {
        $title = $sourceBlogEntry->entry_name;

        $slug = Str::of($sourceBlogEntry->entry_name_seo ?? $title)
            ->slug()
            ->unique('posts', 'slug')
            ->toString();

        $author = $this->findOrCreateAuthor($sourceBlogEntry);

        if (! $author instanceof User) {
            $result->incrementFailed(self::ENTITY_NAME);
            $result->recordFailed(self::ENTITY_NAME, [
                'source_id' => $sourceBlogEntry->entry_id,
                'title' => $title,
                'error' => 'Could not find or create author',
            ]);

            return;
        }

        $content = $sourceBlogEntry->entry_content ?? '';
        $excerpt = Str::of($sourceBlogEntry->entry_content)->stripTags()->limit(200)->toString();

        $post = new Post;
        $post->forceFill([
            'type' => PostType::Blog,
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
            'slug' => $slug,
            'is_published' => true,
            'is_approved' => false,
            'is_featured' => (bool) $sourceBlogEntry->entry_featured,
            'is_pinned' => (bool) $sourceBlogEntry->entry_pinned,
            'comments_enabled' => true,
            'published_at' => $sourceBlogEntry->entry_publish_date
                ? Carbon::createFromTimestamp($sourceBlogEntry->entry_publish_date)
                : Carbon::createFromTimestamp($sourceBlogEntry->entry_date),
            'created_by' => $author->id,
            'created_at' => Carbon::createFromTimestamp($sourceBlogEntry->entry_date),
            'updated_at' => $sourceBlogEntry->entry_last_update
                ? Carbon::createFromTimestamp($sourceBlogEntry->entry_last_update)
                : Carbon::createFromTimestamp($sourceBlogEntry->entry_date),
        ]);

        if (! $isDryRun) {
            $post->save();
            $this->cacheBlogMapping($sourceBlogEntry->entry_id, $post->id);

            if (($imagePath = $sourceBlogEntry->entry_cover_photo) && ($baseUrl = $this->source->getBaseUrl())) {
                $filePath = $this->downloadAndStoreFile(
                    baseUrl: $baseUrl.'/uploads',
                    sourcePath: $imagePath,
                    storagePath: 'post-images',
                );

                if (! is_null($filePath)) {
                    $post->featured_image = $filePath;
                    $post->save();
                }
            }
        }

        $result->incrementMigrated(self::ENTITY_NAME);
        $result->recordMigrated(self::ENTITY_NAME, [
            'source_id' => $sourceBlogEntry->entry_id,
            'target_id' => $post->id ?? 'N/A (dry run)',
            'title' => $post->title,
            'slug' => $post->slug,
            'author' => $author->name,
            'published_at' => $post->published_at?->toDateTimeString() ?? 'N/A',
        ]);
    }

    protected function findOrCreateAuthor(object $sourceBlogEntry): ?User
    {
        $mappedUserId = UserImporter::getUserMapping((int) $sourceBlogEntry->entry_author_id);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        if ($adminUser = User::query()->role(Role::Administrator)->oldest()->first()) {
            return $adminUser;
        }

        return null;
    }

    protected function cacheBlogMapping(int $sourceBlogId, int $targetPostId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceBlogId, $targetPostId, self::CACHE_TTL);
    }
}
