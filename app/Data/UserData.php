<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\WarningConsequenceType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class UserData extends Data
{
    public int $id;

    public ?string $referenceId = null;

    public string $name;

    public string $email;

    public ?string $avatarUrl = null;

    public ?string $signature = null;

    public ?CarbonImmutable $emailVerifiedAt = null;

    /** @var GroupData[] */
    public array $groups = [];

    /** @var FieldData[] */
    public array $fields = [];

    public ?GroupStyleData $displayStyle = null;

    public int $warningPoints = 0;

    public ?WarningConsequenceType $activeConsequenceType = null;

    public bool $hasPassword = false;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
