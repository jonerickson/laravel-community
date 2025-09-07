<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Services\PermissionService;

class PostPolicy
{
    public function viewAny(?User $user): bool
    {
        return PermissionService::hasPermissionTo('view_any_posts', $user);
    }

    public function view(?User $user, Post $post): bool
    {
        return PermissionService::hasPermissionTo('view_posts', $user);
    }

    public function create(?User $user): bool
    {
        return PermissionService::hasPermissionTo('create_posts', $user);
    }

    public function update(?User $user, Post $post): bool
    {
        if (PermissionService::hasPermissionTo('update_posts', $user)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $post->isAuthoredBy($user);
    }

    public function delete(?User $user, Post $post): bool
    {
        if (PermissionService::hasPermissionTo('delete_posts', $user)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $post->isAuthoredBy($user);
    }

    public function report(?User $user, Post $post): bool
    {
        return PermissionService::hasPermissionTo('report_posts', $user);
    }

    public function like(?User $user, Post $post): bool
    {
        return PermissionService::hasPermissionTo('like_posts', $user);
    }

    public function pin(?User $user, Post $post): bool
    {
        return PermissionService::hasPermissionTo('pin_posts', $user);
    }

    public function publish(?User $user, Post $post): bool
    {
        return PermissionService::hasPermissionTo('publish_posts', $user);
    }
}
