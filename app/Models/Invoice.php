<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\InvoiceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Invoice newModelQuery()
 * @method static Builder<static>|Invoice newQuery()
 * @method static Builder<static>|Invoice query()
 * @method static Builder<static>|Invoice whereCreatedAt($value)
 * @method static Builder<static>|Invoice whereId($value)
 * @method static Builder<static>|Invoice whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;
}
