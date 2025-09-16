<?php

declare(strict_types=1);

namespace App\Traits;

use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Eloquent
 */
trait HasReferenceId
{
    public static function bootHasReferenceId(): void
    {
        static::creating(function (Model $model): void {
            $model->fill([
                'reference_id' => Str::uuid()->toString(),
            ]);
        });
    }

    #[Scope]
    public function whereReferenceId(Builder $query, string $id): void
    {
        $query->where('reference_id', $id);
    }

    protected function initializeHasReferenceId(): void
    {
        $this->mergeFillable([
            'reference_id',
        ]);
    }
}
