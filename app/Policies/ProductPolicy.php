<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProductApprovalStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ProductPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Product $product): bool
    {
        return $product->approval_status === ProductApprovalStatus::Approved
            && $product->is_active
            && (blank($product->categories) || $product->categories->some(fn (ProductCategory $category) => Gate::forUser($user)->check('view', $category)));
    }
}
