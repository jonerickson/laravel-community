<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WarningConsequenceType;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
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

    public function view(?User $user, Post $post, ?Forum $forum = null, ?Topic $topic = null): bool
    {
        return Gate::forUser($user)->check('view_posts')
            && (! $post->is_reported || ($post->is_reported && (($user && $post->isAuthoredBy($user)) || Gate::forUser($user)->check('report', $post))))
            && ($post->is_published || (! $post->is_published && (($user && $post->isAuthoredBy($user)) || Gate::forUser($user)->check('publish', $post))))
            && (! $forum instanceof Forum || Gate::forUser($user)->check('view', $forum))
            && (! $topic instanceof Topic || Gate::forUser($user)->check('view', $topic));
    }

    public function create(?User $user, ?Forum $forum = null, ?Topic $topic = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->active_consequence?->type === WarningConsequenceType::PostRestriction || $user->active_consequence?->type === WarningConsequenceType::Ban) {
            return false;
        }

        return Gate::forUser($user)->check('create_posts')
            && (! $forum instanceof Forum || Gate::forUser($user)->check('view', $forum))
            && (! $topic instanceof Topic || Gate::forUser($user)->check('view', $topic));
    }

    public function update(?User $user, Post $post, ?Forum $forum = null, ?Topic $topic = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (Gate::forUser($user)->check('update_posts')) {
            return true;
        }

        return $post->isAuthoredBy($user)
            && $this->view($user, $post, $forum, $topic);
    }

    public function delete(?User $user, Post $post, ?Forum $forum = null, ?Topic $topic = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (Gate::forUser($user)->check('delete_posts')) {
            return true;
        }

        return $post->isAuthoredBy($user)
            && $this->view($user, $post, $forum, $topic);
    }

    public function report(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('report_posts');
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

        return Gate::forUser($user)->check('publish_posts');
    }

    public function approve(?User $user, Post $post): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('approve_posts');
    }
}
