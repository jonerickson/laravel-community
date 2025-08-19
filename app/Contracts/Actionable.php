<?php

declare(strict_types=1);

namespace App\Contracts;

interface Actionable
{
    public function __invoke(): mixed;
}
