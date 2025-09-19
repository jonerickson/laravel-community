<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PaginatedData extends Data
{
    public int $currentPage;

    public int $lastPage;

    public int $perPage;

    public int $total;

    public int $from;

    public int $to;

    public PaginatedLinkData $links;
}

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PaginatedLinkData extends Data
{
    public ?string $first;

    public ?string $last;

    public ?string $next;

    public ?string $prev;
}
