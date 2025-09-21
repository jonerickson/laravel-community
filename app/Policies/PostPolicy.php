<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PostPolicy
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
        return Gate::forUser($user)->check('view_any_posts');
    }

    public function view(?User $user, Post $post): bool
    {
        return Gate::forUser($user)->check('view_posts')
            && $post->is_published;
    }

    public function create(?User $user): bool
    {
        return Gate::forUser($user)->check('create_posts');
    }

    public function update(?User $user, Post $post): bool
    {
        if (! $user) {
            return false;
        }

        if (Gate::forUser($user)->check('update_posts')) {
            return true;
        }

        return $post->isAuthoredBy($user)
            && $post->is_published;
    }

    public function delete(?User $user, Post $post): bool
    {
        if (! $user) {
            return false;
        }

        if (Gate::forUser($user)->check('delete_posts')) {
            return true;
        }

        return $post->isAuthoredBy($user);
    }

    public function report(?User $user, Post $post): bool
    {
        return Gate::forUser($user)->check('report_posts');
    }

    public function like(?User $user, Post $post): bool
    {
        return Gate::forUser($user)->check('like_posts');
    }

    public function pin(?User $user, Post $post): bool
    {
        return Gate::forUser($user)->check('pin_posts');
    }

    public function publish(?User $user, Post $post): bool
    {
        return Gate::forUser($user)->check('publish_posts');
    }
}
