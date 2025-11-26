<?php

declare(strict_types=1);

namespace App\Traits;

use App\Managers\PaymentManager;
use App\Models\Group;
use App\Models\Product;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasGroups
{
    public function groups(): BelongsToMany
    {
        $table = $this->getTable();
        $groupsForeignPivotKey = $this->groupsForeignPivotKey ?? null;

        $relation = $this->belongsToMany(Group::class, $table.'_groups', $groupsForeignPivotKey);

        if (static::class === User::class) {
            return $relation->using(UserGroup::class);
        }

        return $relation;
    }

    public function assignToGroup(Group $group): void
    {
        $this->groups()->syncWithoutDetaching($group);
    }

    public function removeFromGroup(Group $group): void
    {
        $this->groups()->detach($group);
    }

    public function syncGroups(bool $detaching = true): void
    {
        $currentSubscription = null;

        if ($this instanceof User) {
            $paymentManager = app(PaymentManager::class);
            $currentSubscription = $paymentManager->currentSubscription($this);
        }

        // The resource's currently assigned groups
        $currentGroupIds = $this->groups()->pluck('groups.id');

        // All possible groups that can be assigned to a resource based on events such as order history etc.
        $possibleGroupIds = Product::with('groups')
            ->get()
            ->pluck('groups.id');

        // The groups the resource should be assigned based on events such as order history etc.
        $requiredGroupIds = match (true) {
            $this instanceof User => $this->orders()
                ->completed()
                ->with('prices.product.groups')
                ->get()
                ->pluck('prices')
                ->flatten()
                ->pluck('product')
                ->flatten()
                ->filter(function (Product $product) use ($currentSubscription): bool {
                    if ($product->isProduct()) {
                        return true;
                    }

                    if (is_null($currentSubscription)) {
                        return false;
                    }

                    return $product->id === $currentSubscription->product?->id;
                })
                ->pluck('groups')
                ->flatten()
                ->pluck('id')
                ->unique(),
            default => collect(),
        };

        $finalGroups = $currentGroupIds
            ->diff($possibleGroupIds)
            ->add(Group::defaultMemberGroup()->id)
            ->merge($requiredGroupIds)
            ->unique()
            ->reject(fn (int $id): bool => $id === Group::defaultGuestGroup()?->id);

        $this->groups()->sync($finalGroups, $detaching);
    }

    public function hasGroup(Group $group): bool
    {
        return $this->groups()->where('groups.id', $group->id)->exists();
    }

    public function hasAnyGroup(array $groupIds): bool
    {
        return $this->groups()->whereIn('groups.id', $groupIds)->exists();
    }
}
