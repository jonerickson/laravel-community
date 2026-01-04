<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ForumPermissionData extends Data
{
    public function __construct(
        public bool $canCreate = false,
        public bool $canRead = false,
        public bool $canUpdate = false,
        public bool $canDelete = false,
        public bool $canModerate = false,
        public bool $canReply = false,
        public bool $canReport = false,
        public bool $canPin = false,
        public bool $canLock = false,
        public bool $canMove = false,
    ) {
        //
    }
}
