import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { Forum, SharedData, Topic } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Trash } from 'lucide-react';

interface ForumTopicModerationMenuProps {
    topic: Topic;
    forum: Forum;
}

export default function ForumTopicModerationMenu({ topic, forum }: ForumTopicModerationMenuProps) {
    const { auth } = usePage<SharedData>().props;

    const canModerate = topic.created_by === auth.user?.id || auth.isAdmin;

    if (!canModerate) {
        return null;
    }

    const handleDeleteTopic = () => {
        if (window.confirm('Are you sure you want to delete this topic? This action cannot be undone and will delete all posts in this topic.')) {
            router.delete(
                route('forums.topics.destroy', {
                    forum: forum.slug,
                    topic: topic.slug,
                }),
            );
        }
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
                <DropdownMenuItem onClick={handleDeleteTopic} className="text-destructive focus:text-destructive">
                    <Trash className="mr-2 h-4 w-4 text-destructive" />
                    Delete Topic
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
