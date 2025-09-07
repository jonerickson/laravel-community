import { ReportDialog } from '@/components/report-dialog';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useApiRequest } from '@/hooks/use-api-request';
import usePermissions from '@/hooks/use-permissions';
import type { Forum, Post, SharedData, Topic } from '@/types';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Edit, Eye, EyeOff, Flag, MoreHorizontal, Pin, PinOff, Trash } from 'lucide-react';
import { toast } from 'sonner';

interface ForumTopicPostModerationMenuProps {
    post: Post;
    forum: Forum;
    topic: Topic;
}

export default function ForumTopicPostModerationMenu({ post, forum, topic }: ForumTopicPostModerationMenuProps) {
    const { can, cannot, hasAnyPermission } = usePermissions();
    const { auth } = usePage<SharedData>().props;
    const { delete: deletePost } = useForm({
        is_published: post.is_published,
    });
    const { execute: pinPost, loading: pinLoading } = useApiRequest();
    const { execute: publishPost } = useApiRequest();

    if (!hasAnyPermission(['report_posts', 'delete_posts', 'publish_posts', 'pin_posts', 'update_posts']) && post.created_by !== auth?.user?.id) {
        return null;
    }

    const handleDeletePost = () => {
        if (cannot('delete_posts')) {
            return;
        }

        if (!window.confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
            return;
        }

        deletePost(
            route('forums.posts.destroy', {
                forum: forum.slug,
                topic: topic.slug,
                post: post.slug,
            }),
            {
                onSuccess: () => toast.success(`The post has been deleted.`),
                onError: (err) => {
                    console.error('Error deleting post:', err);
                    toast.error(err.message || 'Unable to delete post. Please try again.');
                },
            },
        );
    };

    const handleTogglePublish = async () => {
        if (cannot('publish_posts')) {
            return;
        }

        const isCurrentlyPublished = post.is_published;
        const action = isCurrentlyPublished ? 'unpublish' : 'publish';
        const url = isCurrentlyPublished ? route('api.publish.destroy') : route('api.publish.store');
        const method = isCurrentlyPublished ? 'DELETE' : 'POST';

        if (!window.confirm(`Are you sure you want to ${action} this post?`)) {
            return;
        }

        await publishPost(
            {
                url,
                method,
                data: {
                    post_id: post.id,
                },
            },
            {
                onSuccess: () => {
                    const message = isCurrentlyPublished ? 'The post has been unpublished.' : 'The post has been published.';
                    toast.success(message);
                    window.location.reload();
                },
                onError: (err) => {
                    console.error('Error publishing/unpublishing post:', err);
                    toast.error(err.message || 'Unable to update post. Please try again.');
                },
            },
        );
    };

    const handleTogglePin = async () => {
        if (cannot('pin_posts')) {
            return;
        }

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
                    console.error('Error pinning/unpinning post:', err);
                    toast.error(err.message || 'Unable to update post. Please try again.');
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
                {can('update_posts') && (
                    <DropdownMenuItem asChild>
                        <Link
                            href={route('forums.posts.edit', {
                                forum: forum.slug,
                                topic: topic.slug,
                                post: post.slug,
                            })}
                        >
                            <Edit className="mr-2 h-4 w-4" />
                            Edit Post
                        </Link>
                    </DropdownMenuItem>
                )}

                {can('report_posts') && (
                    <ReportDialog reportableType="App\Models\Post" reportableId={post.id}>
                        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>
                            <Flag className="mr-2 h-4 w-4" />
                            Report Post
                        </DropdownMenuItem>
                    </ReportDialog>
                )}

                {can('pin_posts') && (
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
                )}

                {can('publish_posts') && (
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
                )}

                {can('delete_posts') && (
                    <DropdownMenuItem onClick={handleDeletePost} className="text-destructive focus:text-destructive">
                        <Trash className="mr-2 h-4 w-4 text-destructive" />
                        Delete Post
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
