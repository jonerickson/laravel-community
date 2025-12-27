<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FileVisibility;
use App\Traits\HasReferenceId;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * @property int $id
 * @property string $reference_id
 * @property string|null $resource_type
 * @property int|null $resource_id
 * @property string $name
 * @property string|null $description
 * @property string $path
 * @property string|null $filename
 * @property string|null $mime
 * @property string|null $size
 * @property FileVisibility $visibility
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|Eloquent|null $resource
 * @property-read string|null $url
 *
 * @method static Builder<static>|File newModelQuery()
 * @method static Builder<static>|File newQuery()
 * @method static Builder<static>|File query()
 * @method static Builder<static>|File whereCreatedAt($value)
 * @method static Builder<static>|File whereDescription($value)
 * @method static Builder<static>|File whereFilename($value)
 * @method static Builder<static>|File whereId($value)
 * @method static Builder<static>|File whereMime($value)
 * @method static Builder<static>|File whereName($value)
 * @method static Builder<static>|File wherePath($value)
 * @method static Builder<static>|File whereReferenceId($value)
 * @method static Builder<static>|File whereResourceId($value)
 * @method static Builder<static>|File whereResourceType($value)
 * @method static Builder<static>|File whereSize($value)
 * @method static Builder<static>|File whereUpdatedAt($value)
 * @method static Builder<static>|File whereVisibility($value)
 *
 * @mixin Eloquent
 */
class File extends Model
{
    use HasReferenceId;

    protected $attributes = [
        'visibility' => FileVisibility::Private,
    ];

    protected $fillable = [
        'name',
        'description',
        'filename',
        'path',
        'mime',
        'size',
        'visibility',
    ];

    protected $appends = [
        'url',
    ];

    protected $touches = [
        'resource',
    ];

    public function resource(): MorphTo
    {
        return $this->morphTo('resource');
    }

    public function url(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->path
            ? ($this->visibility === FileVisibility::Public || ! Storage::providesTemporaryUrls()
                ? Storage::url($this->path)
                : Storage::temporaryUrl($this->path, now()->addHour())
            ) : null
        )->shouldCache();
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (File $model): void {
            if ($model->path && Storage::exists($model->path)) {
                $model->forceFill([
                    'size' => Storage::fileSize($model->path),
                    'mime' => Storage::mimeType($model->path),
                ]);
            }
        });

        static::deleting(function (File $model): void {
            if ($model->path && Storage::exists($model->path)) {
                Storage::delete($model->path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'visibility' => FileVisibility::class,
        ];
    }
}
