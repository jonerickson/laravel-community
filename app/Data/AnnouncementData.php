<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AnnouncementType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class AnnouncementData extends Data
{
    public int $id;

    public string $title;

    public string $slug;

    public string $content;

    public AnnouncementType $type;

    public bool $isActive;

    public bool $isDismissible;

    public ?int $createdBy = null;

    public UserData $author;

    public ?CarbonImmutable $startsAt = null;

    public ?CarbonImmutable $endsAt = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
