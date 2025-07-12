<?php

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model implements Sluggable
{
    use HasFactory;
    use HasSlug;

    protected $fillable = [
        'name',
        'description',
        'price',
    ];

    public function generateSlug(): string
    {
        return Str::slug($this->name);
    }
}
