<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string $color
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Topic> $latestTopics
 * @property-read int|null $latest_topics_count
 * @property-read int $posts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Topic> $topics
 * @property-read int $topics_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum active()
 * @method static \Database\Factories\ForumFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Forum whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Forum extends Model implements Sluggable
{
    use HasFactory;
    use HasSlug;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'order',
        'is_active',
    ];

    public function generateSlug(): string
    {
        return Str::slug($this->name);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function latestTopics(): HasMany
    {
        return $this->topics()->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    protected function topicsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->topics()->count()
        );
    }

    protected function postsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => Post::whereHas('topic', fn ($query) => $query->where('forum_id', $this->id))->count()
        );
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
