<?php

declare(strict_types=1);

namespace App\Data;

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
    public int $id;

    public string $title;

    public string $slug;

    public ?string $description;

    public int $forumId;

    public int $createdBy;

    public bool $isPinned;

    public bool $isLocked;

    public int $viewsCount;

    public int $uniqueViewsCount;

    public int $order;

    public int $postsCount;

    public ?CarbonImmutable $lastReplyAt;

    public bool $isReadByUser;

    public int $readsCount;

    public bool $isHot;

    public float $trendingScore;

    #[LoadRelation]
    public ?ForumData $forum;

    #[LoadRelation]
    public UserData $author;

    #[LoadRelation]
    public ?PostData $lastPost;

    /** @var PostData[] */
    public ?array $posts;

    public ?CarbonImmutable $createdAt;

    public ?CarbonImmutable $updatedAt;
}
