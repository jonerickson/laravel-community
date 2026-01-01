<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TopicPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Topic $topic): bool
    {
        return (blank($topic->forum) || Gate::forUser($user)->check('view', $topic->forum))
            && ($topic->posts->some(fn (Post $post) => Gate::forUser($user)->check('view', $post)));
    }

    public function create(?User $user, ?Forum $forum = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return blank($forum) || Gate::forUser($user)->check('create', [$forum, $forum->category]);
    }

    public function update(?User $user, Topic $topic): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $topic)
            && ! $topic->is_locked
            && ((blank($topic->forum) || Gate::forUser($user)->check('update', $topic->forum)) || $topic->isAuthoredBy($user));
    }

    public function delete(?User $user, Topic $topic): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $topic)
            && ! $topic->is_locked
            && ((blank($topic->forum) || Gate::forUser($user)->check('delete', $topic->forum)) || $topic->isAuthoredBy($user));
    }
}
