<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WarningConsequenceType;
use App\Models\Forum;
use App\Models\Post;
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
            && ($topic->posts->some(fn (Post $post) => Gate::forUser($user)->check('view', $post)))
            && (! $forum instanceof Forum || Gate::forUser($user)->check('view', $forum));
    }

    public function create(?User $user, ?Forum $forum = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->active_consequence?->type === WarningConsequenceType::PostRestriction || $user->active_consequence?->type === WarningConsequenceType::Ban) {
            return false;
        }

        return Gate::forUser($user)->check('create_topics')
            && (! $forum instanceof Forum || Gate::forUser($user)->check('view', $forum));
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
            && ! $topic->is_locked
            && $this->view($user, $topic, $forum);
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
            && $this->view($user, $topic, $forum);
    }

    public function reply(?User $user, Topic $topic, ?Forum $forum = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->active_consequence?->type === WarningConsequenceType::PostRestriction || $user->active_consequence?->type === WarningConsequenceType::Ban) {
            return false;
        }

        return Gate::forUser($user)->check('reply_topics')
            && ! $topic->is_locked
            && $this->view($user, $topic, $forum);
    }

    public function report(?User $user, Topic $topic): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('report_topics')
            && $this->view($user, $topic);
    }

    public function pin(?User $user, Topic $topic): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('pin_topics')
            && $this->view($user, $topic);
    }

    public function lock(?User $user, Topic $topic): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('lock_topics')
            && $this->view($user, $topic);
    }
}
