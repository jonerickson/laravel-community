<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Services\PermissionService;

class CommentPolicy
{
    public function viewAny(?User $user): bool
    {
        return PermissionService::hasPermissionTo('view_any_comments', $user);
    }

    public function view(?User $user, Comment $comment): bool
    {
        return PermissionService::hasPermissionTo('view_comments', $user);
    }

    public function create(?User $user): bool
    {
        return PermissionService::hasPermissionTo('create_comments', $user);
    }

    public function update(?User $user, Comment $comment): bool
    {
        if (PermissionService::hasPermissionTo('update_comments')) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $comment->isAuthoredBy($user);
    }

    public function delete(?User $user, Comment $comment): bool
    {
        if (PermissionService::hasPermissionTo('delete_comments')) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $comment->isAuthoredBy($user);
    }

    public function like(?User $user, Comment $comment): bool
    {
        return PermissionService::hasPermissionTo('like_comments', $user);
    }
}
