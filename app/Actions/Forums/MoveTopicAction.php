<?php

declare(strict_types=1);

namespace App\Actions\Forums;

use App\Actions\Action;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Throwable;

class MoveTopicAction extends Action
{
    public function __construct(protected Topic $topic, protected Forum $targetForum)
    {
        //
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): bool
    {
        abort_if(
            boolean: $this->topic->forum_id === $this->targetForum->id,
            code: 422,
            message: 'Topic is already in this forum.'
        );

        return DB::transaction(function (): true {
            $this->topic->forum_id = $this->targetForum->id;
            $this->topic->save();

            return true;
        });
    }
}
