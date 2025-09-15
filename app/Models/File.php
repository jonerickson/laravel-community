<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string|null $resource_type
 * @property int|null $resource_id
 * @property string $name
 * @property string $path
 * @property string|null $filename
 * @property string|null $mime
 * @property string|null $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|File newModelQuery()
 * @method static Builder<static>|File newQuery()
 * @method static Builder<static>|File query()
 * @method static Builder<static>|File whereCreatedAt($value)
 * @method static Builder<static>|File whereFilename($value)
 * @method static Builder<static>|File whereId($value)
 * @method static Builder<static>|File whereMime($value)
 * @method static Builder<static>|File whereName($value)
 * @method static Builder<static>|File wherePath($value)
 * @method static Builder<static>|File whereResourceId($value)
 * @method static Builder<static>|File whereResourceType($value)
 * @method static Builder<static>|File whereSize($value)
 * @method static Builder<static>|File whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class File extends Model
{
    protected $fillable = [
        'name',
        'filename',
        'path',
        'mime',
        'size',
    ];

    protected $appends = [
        'url',
    ];

    public function url(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->path
            ? Storage::temporaryUrl($this->path, now()->addHour())
            : null
        )->shouldCache();
    }

    protected static function booted(): void
    {
        static::creating(function (File $model): void {
            if ($model->path) {
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
}
