<?php

declare(strict_types=1);

arch()->preset()->strict();
arch()->preset()->laravel();

arch()
    ->expect('App\\Actions')
    ->toImplement(App\Contracts\Actionable::class);

arch()
    ->expect('App\\Http\\Controllers\\Api');

arch()
    ->expect('App\\Traits')
    ->toBeTraits();
