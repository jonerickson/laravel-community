import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useApiRequest } from '@/hooks/use-api-request';
import type { Forum, SharedData, Topic } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { MoreHorizontal, Pin, PinOff, Trash } from 'lucide-react';
import { toast } from 'sonner';

interface ForumTopicModerationMenuProps {
    topic: Topic;
    forum: Forum;
}

export default function ForumTopicModerationMenu({ topic, forum }: ForumTopicModerationMenuProps) {
    const { auth } = usePage<SharedData>().props;
    const { delete: deleteTopic } = useForm();
    const { execute: pinTopic, loading: pinLoading } = useApiRequest();
    const canModerate = topic.created_by === auth.user?.id || auth.isAdmin;

    if (!canModerate) {
        return null;
    }

    const handleDeleteTopic = () => {
        if (!window.confirm('Are you sure you want to delete this topic? This action cannot be undone and will delete all posts in this topic.')) {
            return;
        }

        deleteTopic(
            route('forums.topics.destroy', {
                forum: forum.slug,
                topic: topic.slug,
            }),
            {
                onSuccess: () => toast.success(`The topic has been deleted.`),
                onError: (err) => {
                    console.error(err);
                    toast.error(err.message || 'Unable to delete topic. Please try again.');
                },
            },
        );
    };

    const handleTogglePin = async () => {
        const isCurrentlyPinned = topic.is_pinned;
        const url = isCurrentlyPinned ? route('api.pin.destroy') : route('api.pin.store');
        const method = isCurrentlyPinned ? 'DELETE' : 'POST';

        await pinTopic(
            {
                url,
                method,
                data: {
                    topic_id: topic.id,
                },
            },
            {
                onSuccess: () => {
                    const message = isCurrentlyPinned ? 'The topic has been unpinned.' : 'The topic has been pinned.';
                    toast.success(message);
                    window.location.reload();
                },
                onError: (err) => {
                    console.error(err);
                    toast.error(err.message || 'Unable to unpin topic. Please try again.');
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
                <DropdownMenuItem onClick={handleTogglePin} disabled={pinLoading}>
                    {topic.is_pinned ? (
                        <>
                            <PinOff className="mr-2 h-4 w-4" />
                            Unpin Topic
                        </>
                    ) : (
                        <>
                            <Pin className="mr-2 h-4 w-4" />
                            Pin Topic
                        </>
                    )}
                </DropdownMenuItem>
                <DropdownMenuItem onClick={handleDeleteTopic} className="text-destructive focus:text-destructive">
                    <Trash className="mr-2 h-4 w-4 text-destructive" />
                    Delete Topic
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
