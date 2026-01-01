<?php

declare(strict_types=1);

namespace App\Traits;

use App\Data\GroupStyleData;
use App\Managers\PaymentManager;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\ForumCategoryGroup;
use App\Models\ForumGroup;
use App\Models\Group;
use App\Models\Product;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

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

        if (static::class === Forum::class) {
            return $relation
                ->withPivot(['create', 'read', 'update', 'delete', 'moderate', 'reply', 'report', 'pin', 'lock', 'move'])
                ->using(ForumGroup::class);
        }

        if (static::class === ForumCategory::class) {
            return $relation
                ->withPivot(['create', 'read', 'update', 'delete', 'moderate', 'reply', 'report', 'pin', 'lock', 'move'])
                ->using(ForumCategoryGroup::class);
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

    public function displayStyle(): Attribute
    {
        return Attribute::make(
            get: function (): ?GroupStyleData {
                $primaryGroup = $this->groups
                    ->filter(fn (Group $group) => $group->is_active)
                    ->filter(fn (Group $group) => $group->is_visible)
                    ->reject(fn (Group $group): bool => $group->id === Group::defaultGuestGroup()?->id)
                    ->reject(fn (Group $group): bool => $group->id === Group::defaultMemberGroup()?->id)
                    ->sortBy('order')
                    ->first();

                if (! $primaryGroup) {
                    return null;
                }

                return GroupStyleData::from([
                    'color' => $primaryGroup->color,
                    'style' => $primaryGroup->style,
                    'icon' => $primaryGroup->icon
                        ? Storage::url($primaryGroup->icon)
                        : null,
                ]);
            }
        )->shouldCache();
    }
}
