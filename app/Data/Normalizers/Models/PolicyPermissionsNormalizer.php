<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Override;
use Spatie\LaravelData\Normalizers\ModelNormalizer;
use Spatie\LaravelData\Normalizers\Normalized\Normalized;

class PolicyPermissionsNormalizer extends ModelNormalizer
{
    #[Override]
    public function normalize(mixed $value): null|array|Normalized
    {
        if ($value instanceof Model) {
            $value->setAttribute('policy_permissions', [
                'canCreate' => Gate::check('create', $value::class),
                'canRead' => Gate::check('view', $value),
                'canUpdate' => Gate::check('update', $value),
                'canDelete' => Gate::check('delete', $value),
            ]);
        }

        return parent::normalize($value);
    }
}
