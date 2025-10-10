<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Group;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasGroups
{
    public function groups(): BelongsToMany
    {
        $table = $this->getTable();
        $groupsForeignPivotKey = $this->groupsForeignPivotKey ?? null;

        return $this->belongsToMany(Group::class, "{$table}_groups", $groupsForeignPivotKey)
            ->using(UserGroup::class);
    }

    public function assignToGroup(Group $group): void
    {
        $this->groups()->syncWithoutDetaching([$group->id]);
    }

    public function removeFromGroup(Group $group): void
    {
        $this->groups()->detach($group->id);
    }

    public function syncGroups(array $groupIds): void
    {
        $this->groups()->sync($groupIds);
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
