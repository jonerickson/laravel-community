import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useApiRequest } from '@/hooks/use-api-request';
import usePermissions from '@/hooks/use-permissions';
import { Link, router, useForm } from '@inertiajs/react';
import { Edit, Eye, EyeOff, MoreHorizontal, Pin, PinOff, ThumbsDown, ThumbsUp, Trash } from 'lucide-react';

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
    const { execute: approvePost } = useApiRequest();

    if (!hasAnyPermission(['publish_posts', 'pin_posts', 'approve_posts']) && !post.permissions.canDelete && !post.permissions.canUpdate) {
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
                preserveScroll: true,
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
                    router.reload();
                },
            },
        );
    };

    const handleToggleApprove = async () => {
        const isCurrentlyApproved = post.isApproved;
        const action = isCurrentlyApproved ? 'unapprove' : 'approve';
        const url = isCurrentlyApproved ? route('api.approve.destroy') : route('api.approve.store');
        const method = isCurrentlyApproved ? 'DELETE' : 'POST';

        if (!window.confirm(`Are you sure you want to ${action} this post?`)) {
            return;
        }

        await approvePost({
            url,
            method,
            data: {
                type: 'post',
                id: post.id,
            },
        });
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
                    router.reload();
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
                            <Edit />
                            Edit Post
                        </Link>
                    </DropdownMenuItem>
                )}

                {can('approve_posts') && (
                    <DropdownMenuItem onClick={handleToggleApprove}>
                        {post.isApproved ? (
                            <>
                                <ThumbsDown />
                                Unapprove Post
                            </>
                        ) : (
                            <>
                                <ThumbsUp />
                                Approve Post
                            </>
                        )}
                    </DropdownMenuItem>
                )}

                {can('pin_posts') && (
                    <DropdownMenuItem onClick={handleTogglePin} disabled={pinLoading}>
                        {post.isPinned ? (
                            <>
                                <PinOff />
                                Unpin Post
                            </>
                        ) : (
                            <>
                                <Pin />
                                Pin Post
                            </>
                        )}
                    </DropdownMenuItem>
                )}

                {can('publish_posts') && (
                    <DropdownMenuItem onClick={handleTogglePublish}>
                        {post.isPublished ? (
                            <>
                                <EyeOff />
                                Unpublish Post
                            </>
                        ) : (
                            <>
                                <Eye />
                                Publish Post
                            </>
                        )}
                    </DropdownMenuItem>
                )}

                {post.permissions.canDelete && (
                    <DropdownMenuItem onClick={handleDeletePost} className="text-destructive focus:text-destructive">
                        <Trash className="text-destructive" />
                        Delete Post
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
