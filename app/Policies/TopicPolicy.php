<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Forum;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TopicPolicy
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
        return Gate::forUser($user)->check('view_any_topics');
    }

    public function view(?User $user, Topic $topic, ?Forum $forum = null): bool
    {
        return Gate::forUser($user)->check('view_topics')
            && (! $forum instanceof Forum || Gate::forUser($user)->check('view', $forum))
            && ($topic->forum === null || $topic->forum->is_active)
            && ($topic->forum?->category === null || $topic->forum->category->is_active);
    }

    public function create(?User $user, ?Forum $forum = null): bool
    {
        return Gate::forUser($user)->check('create_topics')
            && (! $forum instanceof Forum || Gate::forUser($user)->check('view', $forum))
            && (! $forum instanceof Forum || $forum->is_active)
            && ($forum?->category === null || $forum->category->is_active);
    }

    public function update(?User $user, Topic $topic, ?Forum $forum = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (Gate::forUser($user)->check('update_topics')) {
            return true;
        }

        return $topic->isAuthoredBy($user)
            && (! $forum instanceof Forum || Gate::forUser($user)->check('view', $forum))
            && (! $forum instanceof Forum || $forum->is_active)
            && ($forum?->category === null || $forum->category->is_active);
    }

    public function delete(?User $user, Topic $topic, ?Forum $forum = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (Gate::forUser($user)->check('delete_topics')) {
            return true;
        }

        return $topic->isAuthoredBy($user)
            && (! $forum instanceof Forum || Gate::forUser($user)->check('view', $forum))
            && (! $forum instanceof Forum || $forum->is_active)
            && ($forum?->category === null || $forum->category->is_active);
    }

    public function reply(?User $user, Topic $topic, ?Forum $forum = null): bool
    {
        return Gate::forUser($user)->check('reply_topics')
            && (! $forum instanceof Forum || Gate::forUser($user)->check('view', $forum))
            && (! $forum instanceof Forum || $forum->is_active)
            && ($forum?->category === null || $forum->category->is_active);
    }

    public function report(?User $user, Topic $topic): bool
    {
        return Gate::forUser($user)->check('report_topics');
    }

    public function pin(?User $user, Topic $topic): bool
    {
        return Gate::forUser($user)->check('pin_topics');
    }

    public function lock(?User $user, Topic $topic): bool
    {
        return Gate::forUser($user)->check('lock_topics');
    }
}
