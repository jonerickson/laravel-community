<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_comments');
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->hasPermissionTo('view_comments');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_comments');
    }

    public function update(User $user, Comment $comment): bool
    {
        if ($user->hasPermissionTo('update_comments')) {
            return true;
        }

        return $comment->isAuthoredBy($user);
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($user->hasPermissionTo('delete_comments')) {
            return true;
        }

        return $comment->isAuthoredBy($user);
    }

    public function like(User $user, Comment $comment): bool
    {
        return $user->hasPermissionTo('like_comments');
    }
}
