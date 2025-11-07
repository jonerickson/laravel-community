<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

trait HasFeaturedImage
{
    public function hasFeaturedImage(): bool
    {
        return ! is_null($this->featured_image);
    }

    public function featuredImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->hasFeaturedImage()
                ? Storage::url($this->featured_image)
                : null,
        )->shouldCache();
    }

    protected function initializeHasFeaturedImage(): void
    {
        $this->mergeAppends([
            'featured_image_url',
        ]);

        $this->mergeFillable([
            'featured_image',
        ]);
    }
}
