<?php

namespace App\Support\Csp;

use Illuminate\Support\Facades\Vite;
use Spatie\Csp\Nonce\NonceGenerator as BaseNonceGenerator;

class NonceGenerator implements BaseNonceGenerator
{
    public function generate(): string
    {
        return Vite::cspNonce();
    }
}
