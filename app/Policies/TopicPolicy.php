<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_topics');
    }

    public function view(User $user, Topic $topic): bool
    {
        return $user->hasPermissionTo('view_topics');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_topics');
    }

    public function update(User $user, Topic $topic): bool
    {
        return $user->hasPermissionTo('update_topics')
            || $topic->isAuthoredBy($user);
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $user->hasPermissionTo('delete_topics')
            || $topic->isAuthoredBy($user);
    }

    public function reply(User $user, Topic $topic): bool
    {
        return $user->hasPermissionTo('reply_topics');
    }

    public function report(User $user, Topic $topic): bool
    {
        return $user->hasPermissionTo('report_topics');
    }

    public function pin(User $user, Topic $topic): bool
    {
        return $user->hasPermissionTo('pin_topics');
    }

    public function lock(User $user, Topic $topic): bool
    {
        return $user->hasPermissionTo('lock_topics');
    }
}
