<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Enums\AnnouncementType;
use App\Traits\Activateable;
use App\Traits\HasAuthor;
use App\Traits\HasSlug;
use App\Traits\Readable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property AnnouncementType $type
 * @property bool $is_active
 * @property bool $is_dismissible
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read User|null $creator
 * @property-read bool $is_read_by_user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Read> $reads
 * @property-read int|null $reads_count
 *
 * @method static Builder<static>|Announcement active()
 * @method static Builder<static>|Announcement current()
 * @method static \Database\Factories\AnnouncementFactory factory($count = null, $state = [])
 * @method static Builder<static>|Announcement inactive()
 * @method static Builder<static>|Announcement newModelQuery()
 * @method static Builder<static>|Announcement newQuery()
 * @method static Builder<static>|Announcement query()
 * @method static Builder<static>|Announcement read(?\App\Models\User $user = null)
 * @method static Builder<static>|Announcement unread(?\App\Models\User $user = null)
 * @method static Builder<static>|Announcement whereContent($value)
 * @method static Builder<static>|Announcement whereCreatedAt($value)
 * @method static Builder<static>|Announcement whereCreatedBy($value)
 * @method static Builder<static>|Announcement whereEndsAt($value)
 * @method static Builder<static>|Announcement whereId($value)
 * @method static Builder<static>|Announcement whereIsActive($value)
 * @method static Builder<static>|Announcement whereIsDismissible($value)
 * @method static Builder<static>|Announcement whereSlug($value)
 * @method static Builder<static>|Announcement whereStartsAt($value)
 * @method static Builder<static>|Announcement whereTitle($value)
 * @method static Builder<static>|Announcement whereType($value)
 * @method static Builder<static>|Announcement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Announcement extends Model implements Sluggable
{
    use Activateable;
    use HasAuthor;
    use HasFactory;
    use HasSlug;
    use Readable;

    protected $fillable = [
        'title',
        'content',
        'type',
        'is_dismissible',
        'starts_at',
        'ends_at',
    ];

    public function generateSlug(): ?string
    {
        return Str::slug($this->title);
    }

    public function scopeCurrent(Builder $query): void
    {
        $query->active()
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
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
            'is_dismissible' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'type' => AnnouncementType::class,
        ];
    }
}
