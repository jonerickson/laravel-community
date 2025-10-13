<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ProductPolicy
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

    public function view(?User $user, Product $product): bool
    {
        return $product->isApproved()
            && ($product->categories === null || $product->categories->some(fn (ProductCategory $category) => Gate::forUser($user)->check('view', $category)));
    }
}
