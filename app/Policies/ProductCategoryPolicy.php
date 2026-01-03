<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ProductCategory $category): bool
    {
        return $category->is_active;
    }
}
