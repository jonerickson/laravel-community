<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_posts');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('view_posts');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_posts');
    }

    public function update(User $user, Post $post): bool
    {
        if ($user->hasPermissionTo('update_posts')) {
            return true;
        }

        return $post->isAuthoredBy($user);
    }

    public function delete(User $user, Post $post): bool
    {
        if ($user->hasPermissionTo('delete_posts')) {
            return true;
        }

        return $post->isAuthoredBy($user);
    }

    public function report(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('report_posts');
    }

    public function like(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('like_posts');
    }

    public function pin(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('pin_posts');
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('publish_posts');
    }
}
