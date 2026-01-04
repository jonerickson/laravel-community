<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Data\ForumData;
use App\Data\PostData;
use App\Data\TopicData;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Forums\StorePostRequest;
use App\Http\Requests\Forums\UpdatePostRequest;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Uri;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function store(StorePostRequest $request, Forum $forum, Topic $topic): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('create', Post::class);

        $post = $topic->posts()->create([
            'type' => PostType::Forum,
            'title' => 'Re: '.$topic->title,
            'content' => $request->validated('content'),
        ]);

        $posts = $topic->posts()->paginate();
        $currentPage = Uri::of($request->header('referer'))->query()->integer('page', 1);

        if ($currentPage !== $posts->lastPage()) {
            return to_route('forums.topics.show', [
                'forum' => $forum,
                'topic' => $topic,
                'page' => $posts->lastPage(),
            ])->withFragment((string) $post->id);
        }

        return to_route('forums.topics.show', ['forum' => $forum, 'topic' => $topic])
            ->with('message', 'Your reply was successfully added.')->withFragment((string) $post->id);
    }

    public function edit(Forum $forum, Topic $topic, Post $post): Response
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('update', $post);

        $forum->loadMissing(['category', 'parent.parent.parent']);

        return Inertia::render('forums/posts/edit', [
            'forum' => ForumData::from($forum),
            'topic' => TopicData::from($topic),
            'post' => PostData::from($post),
        ]);
    }

    public function update(UpdatePostRequest $request, Forum $forum, Topic $topic, Post $post): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('update', $post);

        $validated = $request->validated();

        $post->update($validated);

        return to_route('forums.topics.show', ['forum' => $forum, 'topic' => $topic])
            ->with('message', 'The post was successfully updated.');
    }

    public function destroy(Forum $forum, Topic $topic, Post $post): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('delete', $post);

        if ($topic->posts()->count() === 1) {
            return back()->with([
                'message' => 'You cannot delete the last post in a topic. Delete the topic instead.',
                'messageVariant' => 'error',
            ]);
        }

        $post->delete();

        return back()->with('message', 'The post was successfully deleted.');
    }
}
