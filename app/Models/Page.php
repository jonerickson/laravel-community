<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasAuthor;
use App\Traits\HasSlug;
use App\Traits\HasUrl;
use App\Traits\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string $html_content
 * @property string|null $css_content
 * @property string|null $js_content
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property bool $show_in_navigation
 * @property string|null $navigation_label
 * @property int $navigation_order
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $author
 * @property-read string|null $url
 *
 * @method static Builder<static>|Page inNavigation()
 * @method static Builder<static>|Page newModelQuery()
 * @method static Builder<static>|Page newQuery()
 * @method static Builder<static>|Page published()
 * @method static Builder<static>|Page query()
 * @method static Builder<static>|Page unpublished()
 *
 * @mixin \Eloquent
 */
class Page extends Model implements Sluggable
{
    use HasAuthor;
    use HasFactory;
    use HasSlug;
    use HasUrl;
    use Publishable;

    protected $fillable = [
        'title',
        'description',
        'html_content',
        'css_content',
        'js_content',
        'show_in_navigation',
        'navigation_label',
        'navigation_order',
    ];

    public function generateSlug(): ?string
    {
        return Str::slug($this->title);
    }

    public function scopeInNavigation(Builder $query): void
    {
        $query->where('show_in_navigation', true)
            ->orderBy('navigation_order');
    }

    public function getUrl(): ?string
    {
        return route('pages.show', $this->slug);
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_navigation' => 'boolean',
            'navigation_order' => 'integer',
        ];
    }
}
