import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { Forum, Post, SharedData, Topic } from '@/types';
import { router, useForm, usePage } from '@inertiajs/react';
import { Eye, EyeOff, MoreHorizontal, Trash } from 'lucide-react';

interface ForumTopicPostModerationMenuProps {
    post: Post;
    forum: Forum;
    topic: Topic;
}

export default function ForumTopicPostModerationMenu({ post, forum, topic }: ForumTopicPostModerationMenuProps) {
    const { auth } = usePage<SharedData>().props;
    const {
        delete: deletePost,
        patch,
        setData,
        reset,
    } = useForm({
        is_published: post.is_published,
    });

    const canModerate = post.created_by === auth.user?.id || auth.isAdmin;

    if (!canModerate) {
        return null;
    }

    const handleDeletePost = () => {
        if (window.confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
            deletePost(
                route('forums.posts.destroy', {
                    forum: forum.slug,
                    topic: topic.slug,
                    post: post.id,
                }),
            );
        }
    };

    const handleTogglePublish = () => {
        const action = post.is_published ? 'unpublish' : 'publish';

        if (window.confirm(`Are you sure you want to ${action} this post?`)) {
            router.patch(
                route('forums.posts.update', {
                    forum: forum.slug,
                    topic: topic.slug,
                    post: post.id,
                }),
                {
                    is_published: !post.is_published,
                },
            );
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="h-8 w-8 cursor-pointer p-0">
                    <MoreHorizontal className="h-4 w-4" />
                    <span className="sr-only">Open menu</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={handleTogglePublish} className="cursor-pointer">
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
                <DropdownMenuItem onClick={handleDeletePost} className="cursor-pointer text-destructive focus:text-destructive">
                    <Trash className="mr-2 h-4 w-4 text-destructive" />
                    Delete Post
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
