<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\Role;
use App\Models\Group;
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

        $relation = $this->belongsToMany(Group::class, "{$table}_groups", $groupsForeignPivotKey);

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
        $baseGroupIds = match (static::class) {
            User::class => Group::query()->whereHas('roles', function (Builder $query): void {
                $query->whereIn('name', Collection::wrap(Role::cases())->map->value->toArray());
            })->pluck('id'),
            default => collect(),
        };

        $additionalGroupIds = match (static::class) {
            User::class => $this->orders()
                ->completed()
                ->with('products.groups')
                ->get()
                ->pluck('products')
                ->flatten()
                ->pluck('groups')
                ->flatten()
                ->pluck('id')
                ->unique(),
            default => collect(),
        };

        $groupsUnique = $baseGroupIds
            ->merge($additionalGroupIds)
            ->unique();

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
