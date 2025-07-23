<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasFeaturedImage
{
    public function hasFeaturedImage(): bool
    {
        return ! is_null($this->featured_image);
    }

    public function getFeaturedImageUrl(): ?string
    {
        return $this->featured_image ? Storage::url($this->featured_image) : null;
    }
}
