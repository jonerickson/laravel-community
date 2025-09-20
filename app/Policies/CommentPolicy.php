<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CommentPolicy
{
    public function viewAny(?User $user): bool
    {
        return Gate::forUser($user)->check('view_any_comments');
    }

    public function view(?User $user, Comment $comment): bool
    {
        return Gate::forUser($user)->check('view_comments');
    }

    public function create(?User $user): bool
    {
        return Gate::forUser($user)->check('create_comments');
    }

    public function update(?User $user, Comment $comment): bool
    {
        if (Gate::forUser($user)->check('update_comments')) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $comment->isAuthoredBy($user);
    }

    public function delete(?User $user, Comment $comment): bool
    {
        if (Gate::forUser($user)->check('delete_comments')) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $comment->isAuthoredBy($user);
    }

    public function like(?User $user, Comment $comment): bool
    {
        return Gate::forUser($user)->check('like_comments');
    }
}
