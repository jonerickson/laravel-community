<?php

declare(strict_types=1);

namespace App\Actions\Forums;

use App\Actions\Action;
use App\Enums\Role;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteTopicAction extends Action
{
    public function __construct(protected Topic $topic, protected Forum $forum)
    {
        //
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): ?bool
    {
        abort_if(
            boolean: $this->topic->created_by !== Auth::id() && ! request()->user()?->hasRole(Role::Administrator),
            code: 403,
            message: 'You are not authorized to delete this topic.'
        );

        abort_if(
            boolean: $this->topic->forum_id !== $this->forum->id,
            code: 404,
            message: 'Topic not found.'
        );

        return DB::transaction(function () {
            $this->topic->posts()->delete();

            return $this->topic->delete();
        });
    }
}
