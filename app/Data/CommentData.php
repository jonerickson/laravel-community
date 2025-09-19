<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class CommentData extends Data
{
    public int $id;

    public string $commentableType;

    public int $commentableId;

    public string $content;

    public bool $isApproved;

    public int $createdBy;

    public ?int $parentId;

    public ?int $rating;

    public int $likesCount;

    /** @var LikeData[] */
    public array $likesSummary;

    public ?string $userReaction;

    /** @var string[] */
    public array $userReactions;

    public ?UserData $user;

    public ?UserData $author;

    public ?CommentData $parent;

    /** @var CommentData[] */
    public ?array $replies;

    public ?CarbonImmutable $createdAt;

    public ?CarbonImmutable $updatedAt;
}
