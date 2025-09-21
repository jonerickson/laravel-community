<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PolicyCategoryData extends Data
{
    public int $id;

    public string $name;

    public string $slug;

    public ?string $description = null;

    /** @var PolicyData[] */
    public Collection $activePolicies;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
