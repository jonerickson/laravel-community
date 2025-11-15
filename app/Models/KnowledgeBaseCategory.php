<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\Activateable;
use App\Traits\HasColor;
use App\Traits\HasFeaturedImage;
use App\Traits\HasIcon;
use App\Traits\HasSlug;
use App\Traits\Orderable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $color
 * @property string|null $featured_image
 * @property bool $is_active
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, KnowledgeBaseArticle> $articles
 * @property-read int|null $articles_count
 * @property-read string|null $featured_image_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, KnowledgeBaseArticle> $publishedArticles
 * @property-read int|null $published_articles_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory active()
 * @method static \Database\Factories\KnowledgeBaseCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory inactive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereFeaturedImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class KnowledgeBaseCategory extends Model implements Sluggable
{
    use Activateable;
    use HasColor;
    use HasFactory;
    use HasFeaturedImage;
    use HasIcon;
    use HasSlug;
    use Orderable;

    protected $fillable = [
        'name',
        'description',
    ];

    public function generateSlug(): ?string
    {
        return $this->name;
    }

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticle::class, 'category_id');
    }

    public function publishedArticles(): HasMany
    {
        return $this->articles()->where('is_published', true);
    }
}
