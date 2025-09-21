<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function before(User $user): ?bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, File $file): bool
    {
        return true;
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(?User $user, File $file): bool
    {
        return true;
    }

    public function delete(?User $user, File $file): bool
    {
        return true;
    }
}
