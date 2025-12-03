<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PermissionData extends Data
{
    public function __construct(
        public bool $canCreate = false,
        public bool $canUpdate = false,
        public bool $canDelete = false,
    ) {
        //
    }

    public static function fromModel(Model $model): self
    {
        return new self(
            canCreate: Gate::check('create', $model),
            canUpdate: Gate::check('update', $model),
            canDelete: Gate::check('delete', $model),
        );
    }
}
