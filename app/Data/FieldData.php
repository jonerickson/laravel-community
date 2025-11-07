<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FieldType;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class FieldData extends Data
{
    public int $id;

    public string $name;

    public string $label;

    public FieldType $type;

    public ?string $description = null;

    #[LiteralTypeScriptType('Array<{ value: string; label: string }> | null')]
    /**
     * @var array<int, array{value: string, label: string}>|null
     */
    public ?array $options = null;

    public bool $isRequired;

    public bool $isPublic;

    public int $order;

    public ?string $value = null;
}
