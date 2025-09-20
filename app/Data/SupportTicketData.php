<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class SupportTicketData extends Data
{
    public int $id;

    public string $subject;

    public string $description;

    public SupportTicketStatus $status;

    public SupportTicketPriority $priority;

    public int $supportTicketCategoryId;

    #[LoadRelation]
    public ?SupportTicketCategoryData $category;

    public ?int $assignedTo;

    #[LoadRelation]
    public ?UserData $assignedToUser;

    public int $createdBy;

    #[LoadRelation]
    public ?UserData $author;

    public ?string $externalId;

    public ?string $externalUrl;

    public ?CarbonImmutable $lastSyncedAt;

    public ?CarbonImmutable $resolvedAt;

    public ?CarbonImmutable $closedAt;

    public ?CarbonImmutable $createdAt;

    public ?CarbonImmutable $updatedAt;

    #[LoadRelation]
    /** @var CommentData[] */
    public Collection $comments;

    #[LoadRelation]
    /** @var FileData[] */
    public Collection $files;

    public bool $isActive;
}
