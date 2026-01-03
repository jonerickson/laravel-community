<?php

declare(strict_types=1);

namespace App\Data\Normalizers\Forums;

use App\Models\Forum;
use App\Models\ForumCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Override;
use Spatie\LaravelData\Normalizers\ModelNormalizer;
use Spatie\LaravelData\Normalizers\Normalized\Normalized;

class ForumPermissionsNormalizer extends ModelNormalizer
{
    #[Override]
    public function normalize(mixed $value): null|array|Normalized
    {
        if (! $value instanceof Model) {
            return parent::normalize($value);
        }

        if ($value instanceof Forum) {
            $value->setAttribute('forum_permissions', [
                'canCreate' => Gate::check('create', [$value, $value->category]),
                'canRead' => Gate::check('view', $value),
                'canUpdate' => Gate::check('update', $value),
                'canDelete' => Gate::check('delete', $value),
                'canModerate' => Gate::check('moderate', $value),
                'canReply' => Gate::check('reply', $value),
                'canReport' => Gate::check('report', $value),
                'canPin' => Gate::check('pin', $value),
                'canMove' => Gate::check('move', $value),
                'canLock' => Gate::check('lock', $value),
            ]);
        }

        if ($value instanceof ForumCategory) {
            $value->setAttribute('forum_permissions', [
                'canCreate' => Gate::check('create', $value),
                'canRead' => Gate::check('view', $value),
                'canUpdate' => Gate::check('update', $value),
                'canDelete' => Gate::check('delete', $value),
                'canModerate' => Gate::check('moderate', $value),
                'canReply' => Gate::check('reply', $value),
                'canReport' => Gate::check('report', $value),
                'canPin' => Gate::check('pin', $value),
                'canMove' => Gate::check('move', $value),
                'canLock' => Gate::check('lock', $value),
            ]);
        }

        return parent::normalize($value);
    }
}
