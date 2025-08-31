<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicketCategory extends Model implements Sluggable
{
    use HasFactory;
    use HasSlug;

    protected $table = 'support_tickets_categories';

    protected $fillable = [
        'name',
        'description',
        'color',
        'order',
        'is_active',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function activeTickets(): HasMany
    {
        return $this->tickets()->whereIn('status', ['new', 'open', 'in_progress']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }
}
