import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useApiRequest } from '@/hooks/use-api-request';
import type { Forum, Topic } from '@/types';
import { useForm } from '@inertiajs/react';
import { Lock, LockOpen, MoreHorizontal, Pin, PinOff, Trash } from 'lucide-react';
import { toast } from 'sonner';
import usePermissions from '../hooks/use-permissions';

interface ForumTopicModerationMenuProps {
    topic: Topic;
    forum: Forum;
}

export default function ForumTopicModerationMenu({ topic, forum }: ForumTopicModerationMenuProps) {
    const { can, cannot, hasAnyPermission } = usePermissions();
    const { delete: deleteTopic } = useForm();
    const { execute: pinTopic, loading: pinLoading } = useApiRequest();
    const { execute: lockTopic, loading: lockLoading } = useApiRequest();

    if (!hasAnyPermission(['delete_topics', 'pin_topics', 'lock_topics'])) {
        return null;
    }

    const handleDeleteTopic = () => {
        if (cannot('delete_topics')) {
            return;
        }

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
        if (cannot('pin_topics')) {
            return;
        }

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

    const handleToggleLock = async () => {
        if (cannot('lock_topics')) {
            return;
        }

        const isCurrentlyLocked = topic.is_locked;
        const url = isCurrentlyLocked ? route('api.lock.destroy') : route('api.lock.store');
        const method = isCurrentlyLocked ? 'DELETE' : 'POST';

        await lockTopic(
            {
                url,
                method,
                data: {
                    topic_id: topic.id,
                },
            },
            {
                onSuccess: () => {
                    const message = isCurrentlyLocked ? 'The topic has been unlocked.' : 'The topic has been locked.';
                    toast.success(message);
                    window.location.reload();
                },
                onError: (err) => {
                    console.error(err);
                    toast.error(err.message || 'Unable to lock/unlock topic. Please try again.');
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
                {can('pin_topics') && (
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
                )}

                {can('lock_topics') && (
                    <DropdownMenuItem onClick={handleToggleLock} disabled={lockLoading}>
                        {topic.is_locked ? (
                            <>
                                <LockOpen className="mr-2 h-4 w-4" />
                                Unlock Topic
                            </>
                        ) : (
                            <>
                                <Lock className="mr-2 h-4 w-4" />
                                Lock Topic
                            </>
                        )}
                    </DropdownMenuItem>
                )}

                {can('delete_topics') && (
                    <DropdownMenuItem onClick={handleDeleteTopic} className="text-destructive focus:text-destructive">
                        <Trash className="mr-2 h-4 w-4 text-destructive" />
                        Delete Topic
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
