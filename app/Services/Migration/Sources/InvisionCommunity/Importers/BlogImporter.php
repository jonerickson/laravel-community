<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\PostType;
use App\Models\Post;
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

class BlogImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'blogs';

    public const string CACHE_KEY_PREFIX = 'migration:ic:blog_map:';

    public const string CACHE_TAG = 'migration:ic:blog';

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

    /**
     * @return ImporterDependency[]
     */
    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'Blog posts require users to exist for author assignment'),
            ImporterDependency::optionalPost('blog_comments', 'Import blog post comments'),
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

        $totalBlogs = $baseQuery->clone()->countOffset();

        if ($output->isVerbose()) {
            $components->info(sprintf('Found %s blog entries to migrate...', $totalBlogs));
        }

        $progressBar = $output->createProgressBar($totalBlogs);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($blogEntries) use ($config, $result, $progressBar, $output, $components, &$processed): bool {
            foreach ($blogEntries as $sourceBlogEntry) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importBlogEntry($sourceBlogEntry, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVerbose()) {
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

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import blog entry: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
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

    protected function importBlogEntry(object $sourceBlogEntry, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $title = Str::of($sourceBlogEntry->entry_name)
            ->trim()
            ->limit(255, '')
            ->toString();

        $slug = Str::of($sourceBlogEntry->entry_name_seo ?? $title)
            ->trim()
            ->limit(25, '')
            ->slug()
            ->toString();

        $existingPost = Post::query()->where('slug', $slug)->first();

        if ($existingPost) {
            $this->cacheBlogMapping($sourceBlogEntry->entry_id, $existingPost->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceBlogEntry->entry_id,
                    'title' => $title,
                    'slug' => $slug,
                    'reason' => 'Already exists',
                ]);
            }

            return;
        }

        $content = $sourceBlogEntry->entry_content ?? '';
        $excerpt = Str::of($sourceBlogEntry->entry_content)->stripTags()->limit(200)->toString();
        $author = $this->findAuthor($sourceBlogEntry);

        $post = new Post;
        $post->forceFill([
            'type' => PostType::Blog,
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
            'slug' => $slug,
            'is_published' => ! $sourceBlogEntry->entry_hidden,
            'is_approved' => true,
            'is_featured' => (bool) $sourceBlogEntry->entry_featured,
            'is_pinned' => (bool) $sourceBlogEntry->entry_pinned,
            'comments_enabled' => true,
            'published_at' => $sourceBlogEntry->entry_publish_date
                ? Carbon::createFromTimestamp($sourceBlogEntry->entry_publish_date)
                : Carbon::createFromTimestamp($sourceBlogEntry->entry_date),
            'created_by' => $author instanceof User
                ? $author->id
                : null,
            'created_at' => Carbon::createFromTimestamp($sourceBlogEntry->entry_date),
            'updated_at' => $sourceBlogEntry->entry_last_update
                ? Carbon::createFromTimestamp($sourceBlogEntry->entry_last_update)
                : Carbon::createFromTimestamp($sourceBlogEntry->entry_date),
        ]);

        if (! $config->isDryRun) {
            $post->save();
            $this->cacheBlogMapping($sourceBlogEntry->entry_id, $post->id);

            if (($imagePath = $sourceBlogEntry->entry_cover_photo) && ($baseUrl = $this->source->getBaseUrl()) && $config->downloadMedia) {
                $filePath = $this->downloadAndStoreFile(
                    baseUrl: $baseUrl.'/uploads',
                    sourcePath: $imagePath,
                    storagePath: 'blog',
                );

                if (! is_null($filePath)) {
                    $post->featured_image = $filePath;
                    $post->save();
                }
            }
        }

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourceBlogEntry->entry_id,
                'target_id' => $post->id ?? 'N/A (dry run)',
                'title' => $post->title,
                'slug' => $post->slug,
                'author' => $author instanceof User
                    ? $author->name
                    : 'Guest',
                'published_at' => $post->published_at?->toDateTimeString() ?? 'N/A',
            ]);
        }
    }

    protected function findAuthor(object $sourceBlogEntry): ?User
    {
        $mappedUserId = UserImporter::getUserMapping((int) $sourceBlogEntry->entry_author_id);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        return null;
    }

    protected function cacheBlogMapping(int $sourceBlogId, int $targetPostId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceBlogId, $targetPostId, self::CACHE_TTL);
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();
        $config = $this->getConfig();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('entry_status', 'published')
            ->orderBy('entry_id')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('entry_author_id', $config->userId));
    }
}
