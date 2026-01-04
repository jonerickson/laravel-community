<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WarningConsequenceType;
use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Comment $comment): bool
    {
        return $comment->is_approved
            || ($user && $comment->isAuthoredBy($user));
    }

    public function create(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->active_consequence?->type !== WarningConsequenceType::PostRestriction && $user->active_consequence?->type !== WarningConsequenceType::Ban;
    }

    public function update(?User $user, Comment $comment): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $comment)
            && $comment->isAuthoredBy($user);
    }

    public function delete(?User $user, Comment $comment): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $comment)
            && $comment->isAuthoredBy($user);
    }
}
