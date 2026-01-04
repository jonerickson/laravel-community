<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WarningConsequenceType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PostPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Post $post): bool
    {
        return ($post->is_approved || ($user && $post->isAuthoredBy($user) || Gate::forUser($user)->check('moderate', $post->topic?->forum)))
            && ($post->is_published || ($user && $post->isAuthoredBy($user) || Gate::forUser($user)->check('moderate', $post->topic?->forum)))
            && (! $post->is_reported || ($user && $post->isAuthoredBy($user) || Gate::forUser($user)->check('moderate', $post->topic?->forum)))
            && (! $post->published_at || ! $post->published_at->isFuture());
    }

    public function create(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->active_consequence?->type !== WarningConsequenceType::PostRestriction && $user->active_consequence?->type !== WarningConsequenceType::Ban;
    }

    public function update(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($post->isAuthoredBy($user)) {
            return true;
        }

        return $this->view($user, $post)
            && (blank($post->topic?->forum) || Gate::forUser($user)->check('delete', $post->topic->forum));
    }

    public function delete(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($post->isAuthoredBy($user)) {
            return true;
        }

        return $this->view($user, $post)
            && (blank($post->topic?->forum) || Gate::forUser($user)->check('delete', $post->topic->forum));
    }
}
