<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class FilePolicy
{
    public function before(?User $user): ?bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return $user instanceof User;
    }

    public function view(?User $user, File $file, ?Model $resource = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return ! $resource instanceof Model || Gate::forUser($user)->check('view', $resource);
    }

    public function create(?User $user, ?Model $resource = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return ! $resource instanceof Model || Gate::forUser($user)->check('view', $resource);
    }

    public function update(?User $user, File $file, ?Model $resource = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $file, $resource);
    }

    public function delete(?User $user, File $file, ?Model $resource = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $file, $resource);
    }
}
