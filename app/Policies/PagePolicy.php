<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Page $page): bool
    {
        if ($page->is_published) {
            return true;
        }

        return $page->isAuthoredBy($user);
    }

    public function create(?User $user): bool
    {
        return $user instanceof User;
    }

    public function update(?User $user, Page $page): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $page)
            && $page->isAuthoredBy($user);
    }

    public function delete(?User $user, Page $page): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $page)
            && $page->isAuthoredBy($user);
    }
}
