<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Traits\HasAuthor;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reportable_type
 * @property int $reportable_id
 * @property ReportReason $reason
 * @property string|null $additional_info
 * @property ReportStatus $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $admin_notes
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read User $creator
 * @property-read Model|Eloquent $reportable
 * @property-read User|null $reviewer
 *
 * @method static \Database\Factories\ReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereAdditionalInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereAdminNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereReportableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereReportableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Report extends Model
{
    use HasAuthor;
    use HasFactory;

    protected $fillable = [
        'reportable_id',
        'reportable_type',
        'reason',
        'additional_info',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function markAsReviewed(?User $reviewer = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => ReportStatus::Reviewed,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public function approve(?User $reviewer = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => ReportStatus::Approved,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public function reject(?User $reviewer = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => ReportStatus::Rejected,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === ReportStatus::Pending;
    }

    public function isReviewed(): bool
    {
        return $this->status === ReportStatus::Reviewed;
    }

    public function isApproved(): bool
    {
        return $this->status === ReportStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === ReportStatus::Rejected;
    }

    public function getUrl(): ?string
    {
        $reportable = $this->reportable;

        if (! $reportable) {
            return null;
        }

        return match ($this->reportable_type) {
            Post::class => $this->getPostUrl($reportable),
            Topic::class => route('forums.topics.show', [$reportable->forum, $reportable]),
            default => null,
        };
    }

    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    private function getPostUrl(Post $post): ?string
    {
        return match ($post->type->value) {
            'blog' => route('blog.show', $post),
            'forum' => $post->topic
                ? route('forums.topics.show', [$post->topic->forum, $post->topic]).'#post-'.$post->id
                : null,
            default => null,
        };
    }
}
