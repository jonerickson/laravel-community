<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Traits\Commentable;
use App\Traits\HasAuthor;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SupportTicket extends Model
{
    use Commentable;
    use HasAuthor;
    use HasFactory;
    use HasFiles;

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

    public function assignee(): BelongsTo
    {
        return $this->assignedTo();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            SupportTicketStatus::New->value,
            SupportTicketStatus::Open->value,
            SupportTicketStatus::InProgress->value,
        ]);
    }

    public function scopeByStatus($query, SupportTicketStatus $status)
    {
        return $query->where('status', $status->value);
    }

    public function scopeByPriority($query, SupportTicketPriority $priority)
    {
        return $query->where('priority', $priority->value);
    }

    public function scopeAssignedTo($query, User $user)
    {
        return $query->where('assigned_to', $user->id);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeExternal($query, ?string $driver = null)
    {
        $query = $query->whereNotNull('external_id');

        if ($driver !== null && $driver !== '' && $driver !== '0') {
            $query->where('external_driver', $driver);
        }

        return $query;
    }

    public function scopeNeedsSyncing($query, int $hoursOld = 24)
    {
        return $query->external()
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
        return $this->statusEnum()->canTransitionTo($status);
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

    public function statusEnum(): SupportTicketStatus
    {
        return SupportTicketStatus::from($this->status);
    }

    public function priorityEnum(): SupportTicketPriority
    {
        return SupportTicketPriority::from($this->priority);
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket): void {
            if (blank($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }

            if (blank($ticket->status)) {
                $ticket->status = SupportTicketStatus::New;
            }

            if (blank($ticket->priority)) {
                $ticket->priority = SupportTicketPriority::Medium;
            }
        });
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->statusEnum()->label()
        );
    }

    protected function priorityLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->priorityEnum()->label()
        );
    }

    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->statusEnum()->color()
        );
    }

    protected function priorityColor(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->priorityEnum()->color()
        );
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->statusEnum()->isActive()
        );
    }

    protected function assigneeName(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->assignedTo?->name
        );
    }

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'priority' => SupportTicketPriority::class,
            'external_data' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
