<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Traits\HasDataPermissions;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class TopicData extends Data
{
    use HasDataPermissions;

    public int $id;

    public string $title;

    public string $slug;

    public ?string $description = null;

    public int $forumId;

    public int $createdBy;

    public bool $isPinned;

    public bool $isLocked;

    public int $viewsCount;

    public int $uniqueViewsCount;

    public int $order;

    public int $postsCount;

    public ?CarbonImmutable $lastReplyAt = null;

    public bool $isReadByUser;

    public int $readsCount;

    public bool $isHot;

    public float $trendingScore;

    public ?bool $isFollowedByUser = null;

    public ?int $followersCount = null;

    public bool $hasReportedContent = false;

    public bool $hasUnpublishedContent = false;

    #[LoadRelation]
    public ?ForumData $forum = null;

    #[LoadRelation]
    public UserData $author;

    #[LoadRelation]
    public ?PostData $lastPost = null;

    /** @var PostData[] */
    public ?array $posts = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
