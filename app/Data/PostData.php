<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Traits\AddsPolicyPermissions;
use App\Enums\PostType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PostData extends Data
{
    use AddsPolicyPermissions;

    public int $id;

    public PostType $type;

    public string $title;

    public string $slug;

    public ?string $excerpt = null;

    public string $content;

    public bool $isPublished;

    public bool $isApproved;

    public bool $isFeatured;

    public bool $isPinned;

    public bool $commentsEnabled;

    public int $commentsCount;

    public int $likesCount;

    /** @var LikeData[] */
    public array $likesSummary;

    public ?string $userReaction = null;

    /** @var string[] */
    public array $userReactions;

    public ?TopicData $topic = null;

    public ?string $featuredImage = null;

    public ?string $featuredImageUrl = null;

    public ?int $readingTime = null;

    public ?CarbonImmutable $publishedAt = null;

    public int $createdBy;

    public int $viewsCount;

    public bool $isReadByUser;

    public int $readsCount;

    public UserData $author;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $metadata = null;

    /** @var CommentData[] */
    public ?array $comments = null;

    public ?bool $isReported = null;

    public ?int $reportCount = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
