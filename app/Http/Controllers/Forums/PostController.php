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
        $this->authorize('create', [Post::class, $forum, $topic]);

        $validated = $request->validated();

        $topic->posts()->create([
            'type' => PostType::Forum,
            'title' => 'Re: '.$topic->title,
            'content' => $validated['content'],
        ]);

        $totalPosts = $topic->posts()->count();
        $postsPerPage = 10;
        $lastPage = (int) ceil($totalPosts / $postsPerPage);

        return to_route('forums.topics.show', [
            'forum' => $forum,
            'topic' => $topic,
            'page' => $lastPage,
        ])->with('message', 'Your reply was successfully added.');
    }

    /**
     * @throws AuthorizationException
     */
    public function edit(Forum $forum, Topic $topic, Post $post): Response
    {
        $this->authorize('update', [$post, $forum, $topic]);

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
        $this->authorize('update', [$post, $forum, $topic]);

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
        $this->authorize('delete', [$post, $forum, $topic]);

        $post->delete();

        return back()->with('message', 'The post was successfully deleted.');
    }
}
