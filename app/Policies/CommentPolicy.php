<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WarningConsequenceType;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class CommentPolicy
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
        return Gate::forUser($user)->check('view_any_comments');
    }

    public function view(?User $user, Comment $comment, ?Model $commentable = null): bool
    {
        return Gate::forUser($user)->check('view_comments')
            && ($comment->is_approved || (! $comment->is_approved && (($user && $comment->isAuthoredBy($user)) || Gate::forUser($user)->check('update', $comment))))
            && (! $commentable instanceof Model || Gate::forUser($user)->check('view', $commentable));
    }

    public function create(?User $user, ?Model $commentable = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->active_consequence?->type === WarningConsequenceType::PostRestriction || $user->active_consequence?->type === WarningConsequenceType::Ban) {
            return false;
        }

        return Gate::forUser($user)->check('create_comments')
            && (! $commentable instanceof Model || Gate::forUser($user)->check('view', $commentable));
    }

    public function update(?User $user, Comment $comment, ?Model $commentable = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (Gate::forUser($user)->check('update_comments')) {
            return true;
        }

        return $comment->isAuthoredBy($user)
            && $this->view($user, $comment, $commentable);
    }

    public function delete(?User $user, Comment $comment, ?Model $commentable = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (Gate::forUser($user)->check('delete_comments')) {
            return true;
        }

        return $comment->isAuthoredBy($user)
            && $this->view($user, $comment, $commentable);
    }

    public function like(?User $user, Comment $comment): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('like_comments')
            && $this->view($user, $comment);
    }
}
