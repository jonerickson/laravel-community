<?php

declare(strict_types=1);

namespace App\Policies;

use App\Data\PostData;
use App\Enums\WarningConsequenceType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PostPolicy
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
        return Gate::forUser($user)->check('view_any_posts');
    }

    public function view(?User $user, PostData|Post $post): bool
    {
        if ($post instanceof PostData) {
            return Gate::forUser($user)->check('view_posts')
                && ($post->isApproved || ($user && $post->author->id === $user->id) || Gate::forUser($user)->check('approve', Post::find($post->id)))
                && ($post->isPublished || ($user && $post->author->id === $user->id) || Gate::forUser($user)->check('publish', Post::find($post->id)))
                && (! $post->isReported || ($user && $post->author->id === $user->id) || Gate::forUser($user)->check('report', Post::find($post->id)));
        }

        return Gate::forUser($user)->check('view_posts')
            && ($post->is_approved || ($user && $post->isAuthoredBy($user) || Gate::forUser($user)->check('approve', $post)))
            && ($post->is_published || ($user && $post->isAuthoredBy($user) || Gate::forUser($user)->check('publish', $post)))
            && (! $post->is_reported || ($user && $post->isAuthoredBy($user) || Gate::forUser($user)->check('report', $post)));
    }

    public function create(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->active_consequence?->type === WarningConsequenceType::PostRestriction || $user->active_consequence?->type === WarningConsequenceType::Ban) {
            return false;
        }

        return Gate::forUser($user)->check('create_posts');
    }

    public function update(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (Gate::forUser($user)->check('update_posts')) {
            return true;
        }

        return $post->isAuthoredBy($user);
    }

    public function delete(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (Gate::forUser($user)->check('delete_posts')) {
            return true;
        }

        return $post->isAuthoredBy($user);
    }

    public function report(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('report_posts')
            && $this->view($user, $post);
    }

    public function like(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('like_posts')
            && $this->view($user, $post);
    }

    public function pin(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('pin_posts')
            && $this->view($user, $post);
    }

    public function publish(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('publish_posts')
            && $this->view($user, $post);
    }

    public function approve(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('approve_posts')
            && $this->view($user, $post);
    }
}
