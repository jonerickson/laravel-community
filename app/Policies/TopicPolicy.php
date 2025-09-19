<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;
use App\Services\PermissionService;

class TopicPolicy
{
    public function viewAny(?User $user): bool
    {
        return PermissionService::hasPermissionTo('view_any_topics', $user);
    }

    public function view(?User $user, Topic $topic): bool
    {
        return PermissionService::hasPermissionTo('view_topics', $user)
            && $topic->forum->is_active
            && $topic->forum->category->is_active;
    }

    public function create(?User $user): bool
    {
        return PermissionService::hasPermissionTo('create_topics', $user);
    }

    public function update(?User $user, Topic $topic): bool
    {
        if (PermissionService::hasPermissionTo('update_topics')) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $topic->isAuthoredBy($user);
    }

    public function delete(?User $user, Topic $topic): bool
    {
        if (PermissionService::hasPermissionTo('delete_topics')) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $topic->isAuthoredBy($user);
    }

    public function reply(?User $user, Topic $topic): bool
    {
        return PermissionService::hasPermissionTo('reply_topics', $user);
    }

    public function report(?User $user, Topic $topic): bool
    {
        return PermissionService::hasPermissionTo('report_topics', $user);
    }

    public function pin(?User $user, Topic $topic): bool
    {
        return PermissionService::hasPermissionTo('pin_topics', $user);
    }

    public function lock(?User $user, Topic $topic): bool
    {
        return PermissionService::hasPermissionTo('lock_topics', $user);
    }
}
