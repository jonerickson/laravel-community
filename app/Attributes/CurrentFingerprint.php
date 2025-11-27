<?php

declare(strict_types=1);

namespace App\Attributes;

use App\Models\Fingerprint;
use Attribute;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CurrentFingerprint implements ContextualAttribute
{
    /**
     * @throws BindingResolutionException
     */
    public static function resolve(self $attribute, Container $container): ?Fingerprint
    {
        $fingerprintId = $container->make('request')->fingerprintId();

        return Fingerprint::query()
            ->where('fingerprint_id', $fingerprintId)
            ->latest()
            ->first();
    }
}
