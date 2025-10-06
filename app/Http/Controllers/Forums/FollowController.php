<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->resolveModel($type, $id);
        $model->follow();

        return back()->with('message', "You have successfully followed the $type.");
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->resolveModel($type, $id);
        $model->unfollow();

        return back()->with('message', "You have successfully unfollowed the $type.");
    }

    private function resolveModel(string $type, int $id): Model
    {
        $modelClass = match ($type) {
            'forum' => Forum::class,
            'topic' => Topic::class,
            default => abort(404, 'Invalid followable type'),
        };

        return $modelClass::findOrFail($id);
    }
}
