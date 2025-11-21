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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Uri;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
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

    /**
     * @throws AuthorizationException
     */
    public function edit(Forum $forum, Topic $topic, Post $post): Response
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('update', $post);

        return Inertia::render('forums/posts/edit', [
            'forum' => ForumData::from($forum),
            'topic' => TopicData::from($topic),
            'post' => PostData::from($post),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
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

    /**
     * @throws AuthorizationException
     */
    public function destroy(Forum $forum, Topic $topic, Post $post): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('delete', $post);

        $post->delete();

        return to_route('forums.topics.show', ['forum' => $forum, 'topic' => $topic])
            ->with('message', 'The post was successfully deleted.');
    }
}
