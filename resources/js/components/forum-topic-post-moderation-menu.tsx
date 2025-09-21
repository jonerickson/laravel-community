import { ReportDialog } from '@/components/report-dialog';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useApiRequest } from '@/hooks/use-api-request';
import usePermissions from '@/hooks/use-permissions';
import { Link, useForm } from '@inertiajs/react';
import { Edit, Eye, EyeOff, Flag, MoreHorizontal, Pin, PinOff, Trash } from 'lucide-react';
import { toast } from 'sonner';

interface ForumTopicPostModerationMenuProps {
    post: App.Data.PostData;
    forum: App.Data.ForumData;
    topic: App.Data.TopicData;
}

export default function ForumTopicPostModerationMenu({ post, forum, topic }: ForumTopicPostModerationMenuProps) {
    const { can, hasAnyPermission } = usePermissions();
    const { delete: deletePost } = useForm({
        is_published: post.isPublished,
    });
    const { execute: pinPost, loading: pinLoading } = useApiRequest();
    const { execute: publishPost } = useApiRequest();

    if (!hasAnyPermission(['report_posts', 'publish_posts', 'pin_posts']) && !post.permissions.canDelete && !post.permissions.canUpdate) {
        return null;
    }

    const handleDeletePost = () => {
        if (!post.permissions.canDelete) {
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
                onError: (err) => {
                    console.error('Error deleting post:', err);
                    toast.error(err.message || 'Unable to delete post. Please try again.');
                },
            },
        );
    };

    const handleTogglePublish = async () => {
        const isCurrentlyPublished = post.isPublished;
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
            },
        );
    };

    const handleTogglePin = async () => {
        const isCurrentlyPinned = post.isPinned;
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
            },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                    <MoreHorizontal className="size-4" />
                    <span className="sr-only">Open menu</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {post.permissions.canUpdate && (
                    <DropdownMenuItem asChild>
                        <Link
                            href={route('forums.posts.edit', {
                                forum: forum.slug,
                                topic: topic.slug,
                                post: post.slug,
                            })}
                        >
                            <Edit className="mr-2 size-4" />
                            Edit Post
                        </Link>
                    </DropdownMenuItem>
                )}

                {can('report_posts') && (
                    <ReportDialog reportableType="App\Models\Post" reportableId={post.id}>
                        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>
                            <Flag className="mr-2 size-4" />
                            Report Post
                        </DropdownMenuItem>
                    </ReportDialog>
                )}

                {can('pin_posts') && (
                    <DropdownMenuItem onClick={handleTogglePin} disabled={pinLoading}>
                        {post.isPinned ? (
                            <>
                                <PinOff className="mr-2 size-4" />
                                Unpin Post
                            </>
                        ) : (
                            <>
                                <Pin className="mr-2 size-4" />
                                Pin Post
                            </>
                        )}
                    </DropdownMenuItem>
                )}

                {can('publish_posts') && (
                    <DropdownMenuItem onClick={handleTogglePublish}>
                        {post.isPublished ? (
                            <>
                                <EyeOff className="mr-2 size-4" />
                                Unpublish Post
                            </>
                        ) : (
                            <>
                                <Eye className="mr-2 size-4" />
                                Publish Post
                            </>
                        )}
                    </DropdownMenuItem>
                )}

                {post.permissions.canDelete && (
                    <DropdownMenuItem onClick={handleDeletePost} className="text-destructive focus:text-destructive">
                        <Trash className="mr-2 size-4 text-destructive" />
                        Delete Post
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
