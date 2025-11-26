<?php

declare(strict_types=1);

namespace App\Policies;

use App\Data\ProductCategoryData;
use App\Data\ProductData;
use App\Enums\ProductApprovalStatus;
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

    public function view(?User $user, ProductData|Product $product): bool
    {
        if ($product instanceof ProductData) {
            return ($product->approvalStatus === ProductApprovalStatus::Approved)
                && $product->isVisible
                && (blank($product->categories) || collect($product->categories)->some(fn (ProductCategoryData $category) => Gate::getPolicyFor(ProductCategory::class)->view($user, $category)));
        }

        return $product->approval_status === ProductApprovalStatus::Approved
            && $product->is_visible
            && (blank($product->categories) || $product->categories->some(fn (ProductCategory $category) => Gate::forUser($user)->check('view', $category)));
    }
}
