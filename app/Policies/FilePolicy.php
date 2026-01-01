<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function viewAny(?User $user): bool
    {
        return $user instanceof User;
    }

    public function view(?User $user, File $file): bool
    {
        return $user instanceof User;
    }

    public function create(?User $user): bool
    {
        return $user instanceof User;
    }

    public function update(?User $user, File $file): bool
    {
        return $user instanceof User;
    }

    public function delete(?User $user, File $file): bool
    {
        return $user instanceof User;
    }
}
