<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Traits\Commentable;
use App\Traits\HasAuthor;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ticket_number
 * @property string $subject
 * @property string $description
 * @property SupportTicketStatus $status
 * @property SupportTicketPriority $priority
 * @property int $support_ticket_category_id
 * @property int|null $assigned_to
 * @property string|null $external_id
 * @property string|null $external_driver
 * @property array<array-key, mixed>|null $external_data
 * @property int $created_by
 * @property Carbon|null $closed_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $approvedComments
 * @property-read int|null $approved_comments_count
 * @property-read User|null $assignedTo
 * @property-read string|null $assignee_name
 * @property-read User $author
 * @property-read mixed $author_name
 * @property-read SupportTicketCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $comments
 * @property-read int $comments_count
 * @property-read User $creator
 * @property-read File|null $file
 * @property-read \Illuminate\Database\Eloquent\Collection<int, File> $files
 * @property-read int|null $files_count
 * @property-read bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $topLevelComments
 * @property-read int|null $top_level_comments_count
 *
 * @method static Builder<static>|SupportTicket active()
 * @method static Builder<static>|SupportTicket assignedTo(\App\Models\User $user)
 * @method static Builder<static>|SupportTicket byPriority(\App\Enums\SupportTicketPriority $priority)
 * @method static Builder<static>|SupportTicket byStatus(\App\Enums\SupportTicketStatus $status)
 * @method static Builder<static>|SupportTicket external(?string $driver = null)
 * @method static \Database\Factories\SupportTicketFactory factory($count = null, $state = [])
 * @method static Builder<static>|SupportTicket needsSyncing(int $hoursOld = 24)
 * @method static Builder<static>|SupportTicket newModelQuery()
 * @method static Builder<static>|SupportTicket newQuery()
 * @method static Builder<static>|SupportTicket query()
 * @method static Builder<static>|SupportTicket unassigned()
 * @method static Builder<static>|SupportTicket whereAssignedTo($value)
 * @method static Builder<static>|SupportTicket whereClosedAt($value)
 * @method static Builder<static>|SupportTicket whereCreatedAt($value)
 * @method static Builder<static>|SupportTicket whereCreatedBy($value)
 * @method static Builder<static>|SupportTicket whereDescription($value)
 * @method static Builder<static>|SupportTicket whereExternalData($value)
 * @method static Builder<static>|SupportTicket whereExternalDriver($value)
 * @method static Builder<static>|SupportTicket whereExternalId($value)
 * @method static Builder<static>|SupportTicket whereId($value)
 * @method static Builder<static>|SupportTicket whereLastSyncedAt($value)
 * @method static Builder<static>|SupportTicket wherePriority($value)
 * @method static Builder<static>|SupportTicket whereResolvedAt($value)
 * @method static Builder<static>|SupportTicket whereStatus($value)
 * @method static Builder<static>|SupportTicket whereSubject($value)
 * @method static Builder<static>|SupportTicket whereSupportTicketCategoryId($value)
 * @method static Builder<static>|SupportTicket whereTicketNumber($value)
 * @method static Builder<static>|SupportTicket whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SupportTicket extends Model
{
    use Commentable;
    use HasAuthor;
    use HasFactory;
    use HasFiles;

    protected $attributes = [
        'status' => SupportTicketStatus::New,
        'priority' => SupportTicketPriority::Low,
    ];

    protected $fillable = [
        'ticket_number',
        'subject',
        'description',
        'status',
        'priority',
        'support_ticket_category_id',
        'assigned_to',
        'external_id',
        'external_driver',
        'external_data',
        'last_synced_at',
        'closed_at',
        'resolved_at',
    ];

    protected $appends = [
        'is_active',
    ];

    protected $hidden = [
        'external_data',
    ];

    public static function generateTicketNumber(): string
    {
        do {
            $number = 'ST-'.strtoupper(uniqid());
        } while (static::where('ticket_number', $number)->exists());

        return $number;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportTicketCategory::class, 'support_ticket_category_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [
            SupportTicketStatus::New->value,
            SupportTicketStatus::Open->value,
            SupportTicketStatus::InProgress->value,
        ]);
    }

    public function scopeByStatus(Builder $query, SupportTicketStatus $status): void
    {
        $query->where('status', $status->value);
    }

    public function scopeByPriority(Builder $query, SupportTicketPriority $priority): void
    {
        $query->where('priority', $priority->value);
    }

    public function scopeAssignedTo(Builder $query, User $user): void
    {
        $query->where('assigned_to', $user->id);
    }

    public function scopeUnassigned(Builder $query): void
    {
        $query->whereNull('assigned_to');
    }

    public function scopeExternal(Builder $query, ?string $driver = null): void
    {
        $query = $query->whereNotNull('external_id');

        if ($driver !== null && $driver !== '' && $driver !== '0') {
            $query->where('external_driver', $driver);
        }
    }

    public function scopeNeedsSyncing(Builder $query, int $hoursOld = 24): void
    {
        $query->external()
            ->where(function ($query) use ($hoursOld): void {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', Carbon::now()->subHours($hoursOld));
            });
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_to === $user->id;
    }

    public function isExternal(): bool
    {
        return ! is_null($this->external_id) && ! is_null($this->external_driver);
    }

    public function canTransitionTo(SupportTicketStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }

    public function updateStatus(SupportTicketStatus $status): bool
    {
        if (! $this->canTransitionTo($status)) {
            return false;
        }

        return $this->update(['status' => $status->value]);
    }

    public function assign(User $user): bool
    {
        return $this->update(['assigned_to' => $user->id]);
    }

    public function unassign(): bool
    {
        return $this->update(['assigned_to' => null]);
    }

    public function markSynced(): bool
    {
        return $this->update(['last_synced_at' => Carbon::now()]);
    }

    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status->isActive()
        );
    }

    public function assigneeName(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->assignedTo?->name
        );
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket): void {
            if (blank($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'priority' => SupportTicketPriority::class,
            'external_data' => 'array',
            'last_synced_at' => 'datetime',
            'closed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
