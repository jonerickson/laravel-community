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
        $currentSubscriptionGroupId = null;

        if ($this instanceof User) {
            $paymentManager = app(PaymentManager::class);
            $currentSubscription = $paymentManager->currentSubscription($this);

            if ($currentSubscription) {
                $currentSubscriptionGroupId = Product::with('groups')->find($currentSubscription->product->id)->groups->pluck('id');
            }
        }

        // The resource's currently assigned groups
        $currentGroupIds = $this->groups()
            ->pluck('groups.id')
            ->filter()
            ->unique();

        // All possible product groups that can be assigned to a resource based on events such as order history etc.
        $possibleGroupIds = Product::with('groups')
            ->get()
            ->pluck('groups')
            ->flatten()
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        // The product groups the resource should be assigned based on events such as order history etc.
        $requiredProductGroupIds = match (true) {
            $this instanceof User => $this->orders()
                ->completed()
                ->with('prices.product.groups')
                ->get()
                ->pluck('prices')
                ->flatten()
                ->pluck('product')
                ->flatten()
                ->reject(fn (Product $product): bool => $product->isSubscription())
                ->pluck('groups')
                ->flatten()
                ->pluck('id')
                ->filter()
                ->unique()
                ->values(),
            default => collect(),
        };

        $finalGroups = $currentGroupIds
            ->diff($possibleGroupIds)
            ->add(Group::defaultMemberGroup()->id)
            ->merge($currentSubscriptionGroupId)
            ->merge($requiredProductGroupIds)
            ->filter()
            ->unique()
            ->reject(fn (int $id): bool => $id === Group::defaultGuestGroup()?->id)
            ->values();

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
