<?php

declare(strict_types=1);

namespace App\Policies;

use App\Data\ProductCategoryData;
use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function before(?User $user): ?bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ProductCategoryData|ProductCategory $category): bool
    {
        if ($category instanceof ProductCategoryData) {
            return $category->isActive;
        }

        return $category->is_active;
    }
}
