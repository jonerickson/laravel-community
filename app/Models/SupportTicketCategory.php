<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Enums\SupportTicketStatus;
use App\Traits\Activateable;
use App\Traits\HasSlug;
use App\Traits\Orderable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $color
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SupportTicket> $activeTickets
 * @property-read int|null $active_tickets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SupportTicket> $tickets
 * @property-read int|null $tickets_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory active()
 * @method static \Database\Factories\SupportTicketCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory inactive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SupportTicketCategory extends Model implements Sluggable
{
    use Activateable;
    use HasFactory;
    use HasSlug;
    use Orderable;

    protected $table = 'support_tickets_categories';

    protected $fillable = [
        'name',
        'description',
        'color',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function activeTickets(): HasMany
    {
        return $this->tickets()->whereIn('status', [SupportTicketStatus::New, SupportTicketStatus::Open, SupportTicketStatus::InProgress]);
    }

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }
}
