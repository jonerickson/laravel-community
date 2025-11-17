<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Events\PageUpdated;
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
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property \App\Enums\PublishableStatus $status
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read User|null $creator
 * @property-read string|null $url
 *
 * @method static \Database\Factories\PageFactory factory($count = null, $state = [])
 * @method static Builder<static>|Page inNavigation()
 * @method static Builder<static>|Page newModelQuery()
 * @method static Builder<static>|Page newQuery()
 * @method static Builder<static>|Page published()
 * @method static Builder<static>|Page query()
 * @method static Builder<static>|Page recent()
 * @method static Builder<static>|Page unpublished()
 * @method static Builder<static>|Page whereCreatedAt($value)
 * @method static Builder<static>|Page whereCreatedBy($value)
 * @method static Builder<static>|Page whereCssContent($value)
 * @method static Builder<static>|Page whereDescription($value)
 * @method static Builder<static>|Page whereHtmlContent($value)
 * @method static Builder<static>|Page whereId($value)
 * @method static Builder<static>|Page whereIsPublished($value)
 * @method static Builder<static>|Page whereJsContent($value)
 * @method static Builder<static>|Page whereNavigationLabel($value)
 * @method static Builder<static>|Page whereNavigationOrder($value)
 * @method static Builder<static>|Page wherePublishedAt($value)
 * @method static Builder<static>|Page whereShowInNavigation($value)
 * @method static Builder<static>|Page whereSlug($value)
 * @method static Builder<static>|Page whereTitle($value)
 * @method static Builder<static>|Page whereUpdatedAt($value)
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

    protected $dispatchesEvents = [
        'updated' => PageUpdated::class,
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_navigation' => 'boolean',
            'navigation_order' => 'integer',
        ];
    }
}
