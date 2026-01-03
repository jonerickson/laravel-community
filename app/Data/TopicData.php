<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Traits\AddsPolicyPermissions;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class TopicData extends Data
{
    use AddsPolicyPermissions;

    public int $id;

    public string $title;

    public string $slug;

    public ?string $description = null;

    public int $forumId;

    public ?int $createdBy = null;

    public bool $isPinned;

    public bool $isLocked;

    public int $viewsCount;

    public int $order;

    public int $postsCount;

    public bool $isReadByUser;

    public int $readsCount;

    public bool $isHot;

    public float $trendingScore;

    public ?bool $isFollowedByUser = null;

    public ?int $followersCount = null;

    public bool $hasReportedContent = false;

    public bool $hasUnpublishedContent = false;

    public bool $hasUnapprovedContent = false;

    public ?ForumData $forum = null;

    public UserData $author;

    public ?PostData $lastPost = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
