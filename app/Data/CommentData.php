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
class CommentData extends Data
{
    use HasDataPermissions;

    public int $id;

    public string $referenceId;

    public string $commentableType;

    public int $commentableId;

    public string $content;

    public bool $isApproved;

    public int $createdBy;

    public ?int $parentId = null;

    public ?int $rating = null;

    public int $likesCount;

    /** @var LikeData[] */
    public array $likesSummary;

    public ?string $userReaction = null;

    /** @var string[] */
    public array $userReactions;

    #[LoadRelation]
    public ?UserData $user = null;

    #[LoadRelation]
    public ?UserData $author = null;

    #[LoadRelation]
    public ?CommentData $parent = null;

    /** @var CommentData[] */
    public ?array $replies = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
