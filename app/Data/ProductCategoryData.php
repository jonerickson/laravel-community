<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProductCategoryData extends Data
{
    public int $id;

    public string $name;

    public string $slug;

    public ?string $description;

    public ?ImageData $image;
}
