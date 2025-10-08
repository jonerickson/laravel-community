<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @template T
 */
#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PaginatedData extends Data
{
    /**
     * @param  array<T>  $data
     */
    public function __construct(
        #[LiteralTypeScriptType('Array<T>')]
        public array $data,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
        public ?int $from = null,
        public ?int $to = null,
        public ?PaginatedLinkData $links = null,
    ) {}
}

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PaginatedLinkData extends Data
{
    public ?string $first = null;

    public ?string $last = null;

    public ?string $next = null;

    public ?string $prev = null;
}
