<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Enums\AnnouncementType;
use App\Traits\HasAuthor;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property AnnouncementType $type
 * @property bool $is_active
 * @property bool $is_dismissible
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement current()
 * @method static \Database\Factories\AnnouncementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereIsDismissible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Announcement extends Model implements Sluggable
{
    use HasAuthor;
    use HasFactory;
    use HasSlug;

    protected $fillable = [
        'title',
        'content',
        'type',
        'is_active',
        'is_dismissible',
        'starts_at',
        'ends_at',
    ];

    public function generateSlug(): ?string
    {
        return Str::slug($this->title);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        $now = now();

        return $query->active()
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    public function isActive(): bool
    {
        return $this->is_active && $this->isCurrent();
    }

    public function isCurrent(): bool
    {
        $now = now();
        $start = $this->starts_at ?? $now->copy()->subYear();
        $end = $this->ends_at ?? $now->copy()->addYear();

        return $now->isBetween($start, $end);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_dismissible' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'type' => AnnouncementType::class,
        ];
    }
}
