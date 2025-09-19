<?php

declare(strict_types=1);

namespace App\Data;

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
    public int $id;

    public PostType $type;

    public string $title;

    public string $slug;

    public ?string $excerpt;

    public string $content;

    public bool $isPublished;

    public bool $isFeatured;

    public bool $isPinned;

    public bool $commentsEnabled;

    public int $commentsCount;

    public int $likesCount;

    /** @var LikeData[] */
    public array $likesSummary;

    public ?string $userReaction;

    /** @var string[] */
    public array $userReactions;

    public ?int $topicId;

    public ?string $featuredImage;

    public ?string $featuredImageUrl;

    public ?int $readingTime;

    public ?CarbonImmutable $publishedAt;

    public int $createdBy;

    public int $viewsCount;

    public bool $isReadByUser;

    public int $readsCount;

    public UserData $author;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $metadata;

    public ?CarbonImmutable $createdAt;

    public ?CarbonImmutable $updatedAt;

    /** @var CommentData[] */
    public ?array $comments;

    public ?bool $isReported;

    public ?int $reportCount;
}
