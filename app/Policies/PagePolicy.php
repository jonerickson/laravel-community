<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PagePolicy
{
    public function viewAny(?User $user): bool
    {
        return Gate::forUser($user)->check('view_any_pages');
    }

    public function view(?User $user, Page $page): bool
    {
        if ($page->is_published) {
            return true;
        }
        if (Gate::forUser($user)->check('view_pages')) {
            return true;
        }

        return $page->isAuthoredBy($user);
    }

    public function create(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('create_pages');
    }

    public function update(?User $user, Page $page): bool
    {
        if (! $user instanceof User) {
            return false;
        }
        if (Gate::forUser($user)->check('update_pages')) {
            return true;
        }

        return $page->isAuthoredBy($user);
    }

    public function delete(?User $user, Page $page): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('delete_pages');
    }

    public function publish(?User $user, Page $page): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('publish_pages');
    }
}
