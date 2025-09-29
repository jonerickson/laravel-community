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
    public ?OrderData $order = null;

    #[LoadRelation]
    public ?SupportTicketCategoryData $category = null;

    public ?int $assignedTo = null;

    #[LoadRelation]
    public ?UserData $assignedToUser = null;

    public int $createdBy;

    #[LoadRelation]
    public ?UserData $author = null;

    public ?string $externalId = null;

    public ?string $externalUrl = null;

    public ?CarbonImmutable $lastSyncedAt = null;

    public ?CarbonImmutable $resolvedAt = null;

    public ?CarbonImmutable $closedAt = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;

    #[LoadRelation]
    /** @var CommentData[] */
    public Collection $comments;

    #[LoadRelation]
    /** @var FileData[] */
    public Collection $files;

    public bool $isActive;
}
