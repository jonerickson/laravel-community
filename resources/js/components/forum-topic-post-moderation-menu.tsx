import { ReportDialog } from '@/components/report-dialog';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useApiRequest } from '@/hooks/use-api-request';
import type { Forum, Post, SharedData, Topic } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { Eye, EyeOff, Flag, MoreHorizontal, Pin, PinOff, Trash } from 'lucide-react';
import { toast } from 'sonner';

interface ForumTopicPostModerationMenuProps {
    post: Post;
    forum: Forum;
    topic: Topic;
}

export default function ForumTopicPostModerationMenu({ post, forum, topic }: ForumTopicPostModerationMenuProps) {
    const { auth } = usePage<SharedData>().props;
    const { delete: deletePost } = useForm({
        is_published: post.is_published,
    });
    const { patch: updatePost, transform: transformUpdatePost } = useForm();
    const { execute: pinPost, loading: pinLoading } = useApiRequest();

    const canModerate = post.created_by === auth.user?.id || auth.isAdmin;
    const canReport = auth.user && auth.user.id !== post.created_by && !post.is_reported;

    if (!canModerate && !canReport) {
        return null;
    }

    const handleDeletePost = () => {
        if (!window.confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
            return;
        }

        deletePost(
            route('forums.posts.destroy', {
                forum: forum.slug,
                topic: topic.slug,
                post: post.id,
            }),
            {
                onSuccess: () => toast.success(`The post has been deleted.`),
                onError: (err) => {
                    console.error(err);
                    toast.error(err.message || 'There was an error. Please try again.');
                },
            },
        );
    };

    const handleTogglePublish = () => {
        const action = post.is_published ? 'unpublish' : 'publish';

        if (!window.confirm(`Are you sure you want to ${action} this post?`)) {
            return;
        }

        transformUpdatePost(() => ({
            is_published: !post.is_published,
        }));

        updatePost(
            route('forums.posts.update', {
                forum: forum.slug,
                topic: topic.slug,
                post: post.id,
            }),
            {
                onSuccess: () => toast.success(`The post has been ${action}ed.`),
                onError: (err) => {
                    console.error(err);
                    toast.error(err.message || 'There was an error. Please try again.');
                },
            },
        );
    };

    const handleTogglePin = async () => {
        const isCurrentlyPinned = post.is_pinned;
        const url = isCurrentlyPinned ? route('api.pin.destroy') : route('api.pin.store');
        const method = isCurrentlyPinned ? 'DELETE' : 'POST';

        await pinPost(
            {
                url,
                method,
                data: {
                    post_id: post.id,
                },
            },
            {
                onSuccess: () => {
                    const message = isCurrentlyPinned ? 'The post has been unpinned.' : 'The post has been pinned.';
                    toast.success(message);
                    window.location.reload();
                },
                onError: (err) => {
                    console.error(err);
                    toast.error(err.message || 'There was an error. Please try again.');
                },
            },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                    <MoreHorizontal className="h-4 w-4" />
                    <span className="sr-only">Open menu</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {canReport && (
                    <ReportDialog reportableType="App\Models\Post" reportableId={post.id}>
                        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>
                            <Flag className="mr-2 h-4 w-4" />
                            Report Post
                        </DropdownMenuItem>
                    </ReportDialog>
                )}

                {canModerate && (
                    <>
                        <DropdownMenuItem onClick={handleTogglePin} disabled={pinLoading}>
                            {post.is_pinned ? (
                                <>
                                    <PinOff className="mr-2 h-4 w-4" />
                                    Unpin Post
                                </>
                            ) : (
                                <>
                                    <Pin className="mr-2 h-4 w-4" />
                                    Pin Post
                                </>
                            )}
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={handleTogglePublish}>
                            {post.is_published ? (
                                <>
                                    <EyeOff className="mr-2 h-4 w-4" />
                                    Unpublish Post
                                </>
                            ) : (
                                <>
                                    <Eye className="mr-2 h-4 w-4" />
                                    Publish Post
                                </>
                            )}
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={handleDeletePost} className="text-destructive focus:text-destructive">
                            <Trash className="mr-2 h-4 w-4 text-destructive" />
                            Delete Post
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
