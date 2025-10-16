<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\PostType;
use App\Models\Post;
use App\Models\User;
use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunityLanguageResolver;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlogImporter implements EntityImporter
{
    protected const string ENTITY_NAME = 'blogs';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:blog_map:';

    public function __construct(
        protected ?InvisionCommunityLanguageResolver $languageResolver = null,
    ) {}

    public static function getBlogMapping(int $sourceBlogId): ?int
    {
        return Cache::get(self::CACHE_KEY_PREFIX.$sourceBlogId);
    }

    public static function clearBlogMappingCache(): void
    {
        $keys = Cache::get('migration:ic:blog_map_keys', []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget('migration:ic:blog_map_keys');
    }

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'Blog posts require users to exist for author assignment'),
            ImporterDependency::optionalPost('blog_comments', 'Import the comments that belongs to the posts'),
        ];
    }

    public function import(
        string $connection,
        int $batchSize,
        bool $isDryRun,
        OutputStyle $output,
        MigrationResult $result,
    ): void {
        if (! $this->languageResolver instanceof InvisionCommunityLanguageResolver) {
            $this->languageResolver = new InvisionCommunityLanguageResolver($connection);
        }

        $totalBlogs = DB::connection($connection)
            ->table('blog_entries')
            ->where('entry_status', 'published')
            ->count();

        $output->writeln("Found {$totalBlogs} blog entries to migrate...");

        $progressBar = $output->createProgressBar($totalBlogs);
        $progressBar->start();

        DB::connection($connection)
            ->table('blog_entries')
            ->where('entry_status', 'published')
            ->orderBy('entry_id')
            ->chunk($batchSize, function ($sourceBlogEntries) use ($isDryRun, $result, $progressBar, $output): void {
                foreach ($sourceBlogEntries as $sourceBlogEntry) {
                    try {
                        $this->importBlogEntry($sourceBlogEntry, $isDryRun, $result);
                    } catch (Exception $e) {
                        $result->incrementFailed(self::ENTITY_NAME);
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceBlogEntry->entry_id ?? 'unknown',
                            'title' => $sourceBlogEntry->entry_name ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);

                        Log::error('Failed to import blog entry', [
                            'source_id' => $sourceBlogEntry->entry_id ?? 'unknown',
                            'title' => $sourceBlogEntry->entry_name ?? 'unknown',
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        $fileName = Str::of($e->getFile())->classBasename();
                        $output->writeln("<error>Failed to import blog entry: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                    }

                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $output->newLine(2);
    }

    protected function importBlogEntry(object $sourceBlogEntry, bool $isDryRun, MigrationResult $result): void
    {
        $title = $sourceBlogEntry->entry_name;
        $slug = $sourceBlogEntry->entry_name_seo ?? Str::slug($title);

        $existingPost = Post::query()
            ->where('type', PostType::Blog)
            ->where(function ($query) use ($title, $slug): void {
                $query->where('title', $title)
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existingPost) {
            $this->cacheBlogMapping($sourceBlogEntry->entry_id, $existingPost->id);
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceBlogEntry->entry_id,
                'title' => $title,
                'reason' => 'Already exists',
            ]);

            return;
        }

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

        $content = $this->cleanHtml($sourceBlogEntry->entry_content ?? '');
        $excerpt = Str::of($content)->stripTags()->limit(200)->toString();

        $post = new Post;
        $post->forceFill([
            'type' => PostType::Blog,
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
            'slug' => $slug,
            'is_published' => true,
            'is_approved' => ! $sourceBlogEntry->entry_hidden,
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

        return null;
    }

    protected function cleanHtml(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        return $html;
    }

    protected function cacheBlogMapping(int $sourceBlogId, int $targetPostId): void
    {
        Cache::forever(self::CACHE_KEY_PREFIX.$sourceBlogId, $targetPostId);
    }
}
