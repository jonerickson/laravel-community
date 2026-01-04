<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WarningConsequenceType;
use App\Events\CommentCreated;
use App\Events\CommentDeleted;
use App\Events\CommentUpdated;
use App\Traits\Approvable;
use App\Traits\HasAuthor;
use App\Traits\HasReferenceId;
use App\Traits\Likeable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $reference_id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property string|null $content
 * @property int|null $rating
 * @property bool $is_approved
 * @property int|null $parent_id
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read Model|Eloquent $commentable
 * @property-read User|null $creator
 * @property-read Collection<int, Like> $likes
 * @property-read int|null $likes_count
 * @property-read array $likes_summary
 * @property-read Comment|null $parent
 * @property-read Collection<int, Comment> $replies
 * @property-read int|null $replies_count
 * @property-read string|null $user_reaction
 * @property-read array $user_reactions
 *
 * @method static Builder<static>|Comment approved()
 * @method static Builder<static>|Comment comments()
 * @method static \Database\Factories\CommentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Comment newModelQuery()
 * @method static Builder<static>|Comment newQuery()
 * @method static Builder<static>|Comment pending()
 * @method static Builder<static>|Comment query()
 * @method static Builder<static>|Comment ratings()
 * @method static Builder<static>|Comment topLevel()
 * @method static Builder<static>|Comment unapproved()
 * @method static Builder<static>|Comment whereCommentableId($value)
 * @method static Builder<static>|Comment whereCommentableType($value)
 * @method static Builder<static>|Comment whereContent($value)
 * @method static Builder<static>|Comment whereCreatedAt($value)
 * @method static Builder<static>|Comment whereCreatedBy($value)
 * @method static Builder<static>|Comment whereId($value)
 * @method static Builder<static>|Comment whereIsApproved($value)
 * @method static Builder<static>|Comment whereParentId($value)
 * @method static Builder<static>|Comment whereRating($value)
 * @method static Builder<static>|Comment whereReferenceId($value)
 * @method static Builder<static>|Comment whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Comment extends Model
{
    use Approvable;
    use HasAuthor;
    use HasFactory;
    use HasReferenceId;
    use Likeable;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'content',
        'rating',
        'parent_id',
    ];

    protected $touches = [
        'commentable',
    ];

    protected $dispatchesEvents = [
        'created' => CommentCreated::class,
        'updated' => CommentUpdated::class,
        'deleting' => CommentDeleted::class,
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('is_approved', false);
    }

    public function scopeTopLevel(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function isReply(): bool
    {
        return ! is_null($this->parent_id);
    }

    public function isRating(): bool
    {
        return ! is_null($this->rating);
    }

    public function scopeRatings(Builder $query): void
    {
        $query->whereNotNull('rating');
    }

    public function scopeComments(Builder $query): void
    {
        $query->whereNull('rating');
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (Comment $comment): void {
            if ($author = $comment->author) {
                $requiresModeration = $author->active_consequence?->type === WarningConsequenceType::ModerateContent;

                $comment->forceFill([
                    'is_approved' => ! $requiresModeration,
                ]);
            }
        });
    }
}
