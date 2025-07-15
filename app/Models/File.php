<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereResourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereResourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereUpdatedAt($value)
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

    protected static function booted()
    {
        static::creating(function (File $model) {
            $model->forceFill([
                'size' => Storage::fileSize($model->path),
                'mime' => Storage::mimeType($model->path),
            ]);
        });
    }
}
