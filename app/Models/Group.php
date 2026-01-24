<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GroupStyleType;
use App\Events\GroupSaving;
use App\Traits\Activateable;
use App\Traits\HasFiles;
use App\Traits\HasIcon;
use App\Traits\Orderable;
use App\Traits\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property string $color
 * @property GroupStyleType $style
 * @property string|null $icon
 * @property int $order
 * @property bool $is_active
 * @property bool $is_visible
 * @property bool $is_default_guest
 * @property bool $is_default_member
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, GroupDiscordRole> $discordRoles
 * @property-read int|null $discord_roles_count
 * @property-read File|null $file
 * @property-read Collection<int, File> $files
 * @property-read int|null $files_count
 * @property-read UserGroup|ForumGroup|ForumCategoryGroup|null $pivot
 * @property-read Collection<int, ForumCategory> $forumCategories
 * @property-read int|null $forum_categories_count
 * @property-read Collection<int, Forum> $forums
 * @property-read int|null $forums_count
 * @property-read Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static Builder<static>|Group active()
 * @method static Builder<static>|Group defaultGuestGroups()
 * @method static Builder<static>|Group defaultMemberGroups()
 * @method static \Database\Factories\GroupFactory factory($count = null, $state = [])
 * @method static Builder<static>|Group hidden()
 * @method static Builder<static>|Group inactive()
 * @method static Builder<static>|Group newModelQuery()
 * @method static Builder<static>|Group newQuery()
 * @method static Builder<static>|Group ordered()
 * @method static Builder<static>|Group permission($permissions, $without = false)
 * @method static Builder<static>|Group query()
 * @method static Builder<static>|Group role($roles, $guard = null, $without = false)
 * @method static Builder<static>|Group visible()
 * @method static Builder<static>|Group whereColor($value)
 * @method static Builder<static>|Group whereCreatedAt($value)
 * @method static Builder<static>|Group whereDescription($value)
 * @method static Builder<static>|Group whereIcon($value)
 * @method static Builder<static>|Group whereId($value)
 * @method static Builder<static>|Group whereImage($value)
 * @method static Builder<static>|Group whereIsActive($value)
 * @method static Builder<static>|Group whereIsDefaultGuest($value)
 * @method static Builder<static>|Group whereIsDefaultMember($value)
 * @method static Builder<static>|Group whereIsVisible($value)
 * @method static Builder<static>|Group whereName($value)
 * @method static Builder<static>|Group whereOrder($value)
 * @method static Builder<static>|Group whereStyle($value)
 * @method static Builder<static>|Group whereUpdatedAt($value)
 * @method static Builder<static>|Group withoutPermission($permissions)
 * @method static Builder<static>|Group withoutRole($roles, $guard = null)
 *
 * @mixin \Eloquent
 */
class Group extends Model
{
    use Activateable;
    use HasFactory;
    use HasFiles;
    use HasIcon;
    use HasRoles;
    use Orderable;
    use Visible;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'description',
        'image',
        'color',
        'style',
        'is_default_guest',
        'is_default_member',
    ];

    protected $dispatchesEvents = [
        'saving' => GroupSaving::class,
    ];

    protected static ?Group $defaultGuestGroup = null;

    protected static ?Group $defaultMemberGroup = null;

    public static function resetDefaultGroupCache(): void
    {
        static::$defaultGuestGroup = null;
        static::$defaultMemberGroup = null;
    }

    public static function defaultGuestGroup(): ?Group
    {
        return static::$defaultGuestGroup ?? static::$defaultGuestGroup = Cache::memo()->remember('default_guest_group', now()->addHour(), fn () => Group::query()->with(['permissions', 'roles'])->defaultGuestGroups()->first());
    }

    public static function defaultMemberGroup(): ?Group
    {
        return static::$defaultMemberGroup ?? static::$defaultMemberGroup = Cache::memo()->remember('default_member_group', now()->addHour(), fn () => Group::query()->with(['permissions', 'roles'])->defaultMemberGroups()->first());
    }

    public function forums(): BelongsToMany
    {
        return $this->belongsToMany(Forum::class, 'forums_groups')
            ->withPivot(['read', 'write', 'delete'])
            ->using(ForumGroup::class);
    }

    public function forumCategories(): BelongsToMany
    {
        return $this->belongsToMany(ForumCategory::class, 'forums_categories_groups', 'group_id', 'category_id')
            ->withPivot(['read', 'write', 'delete'])
            ->using(ForumCategoryGroup::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_groups')
            ->using(UserGroup::class);
    }

    public function discordRoles(): HasMany
    {
        return $this->hasMany(GroupDiscordRole::class);
    }

    public function scopeDefaultMemberGroups(Builder $query): void
    {
        $query->where('is_default_member', true);
    }

    public function scopeDefaultGuestGroups(Builder $query): void
    {
        $query->where('is_default_guest', true);
    }

    public function toggleDefaultGuestGroup(): void
    {
        $builder = static::query()
            ->whereKeyNot($this->id)
            ->defaultGuestGroups();

        if ($builder->exists()) {
            $builder->update([
                'is_default_guest' => false,
            ]);
        }
    }

    public function toggleDefaultMemberGroup(): void
    {
        $builder = static::query()
            ->whereKeyNot($this->id)
            ->defaultMemberGroups();

        if ($builder->exists()) {
            $builder->update([
                'is_default_member' => false,
            ]);
        }
    }

    protected function getDefaultGuardName(): string
    {
        return 'web';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'style' => GroupStyleType::class,
            'is_default_member' => 'boolean',
            'is_default_guest' => 'boolean',
        ];
    }
}
