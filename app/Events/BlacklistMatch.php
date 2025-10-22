<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Blacklist;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BlacklistMatch implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $content,
        public Blacklist $blacklist,
        public ?User $user = null
    ) {
        //
    }
}
