<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\Role;
use App\Managers\PaymentManager;
use App\Models\Group;
use App\Models\Product;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

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

        $currentGroupIds = $this->groups()->pluck('groups.id');

        $baseGroupIds = match (true) {
            $this instanceof User => Group::query()->whereHas('roles', function (Builder $query): void {
                $query->whereIn('name', Collection::wrap(Role::cases())->map->value->toArray());
            })->whereKeyNot(Group::defaultGuestGroup())->pluck('id')->intersect($currentGroupIds),
            default => collect(),
        };

        $additionalGroupIds = match (true) {
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

                    if ($currentSubscription === null) {
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

        $groupsUnique = $baseGroupIds
            ->add(Group::defaultMemberGroup()->id)
            ->merge($additionalGroupIds)
            ->unique()
            ->reject(fn (int $id) => $id === Group::defaultGuestGroup()?->id);

        $this->groups()->sync($groupsUnique, $detaching);
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
