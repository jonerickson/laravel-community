import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useApiRequest } from '@/hooks/use-api-request';
import usePermissions from '@/hooks/use-permissions';
import { router, useForm } from '@inertiajs/react';
import { Lock, LockOpen, MoreHorizontal, Pin, PinOff, Trash } from 'lucide-react';

interface ForumTopicModerationMenuProps {
    topic: App.Data.TopicData;
    forum: App.Data.ForumData;
}

export default function ForumTopicModerationMenu({ topic, forum }: ForumTopicModerationMenuProps) {
    const { can, hasAnyPermission } = usePermissions();
    const { delete: deleteTopic } = useForm();
    const { execute: pinTopic, loading: pinLoading } = useApiRequest();
    const { execute: lockTopic, loading: lockLoading } = useApiRequest();

    if (!hasAnyPermission(['delete_topics', 'pin_topics', 'lock_topics'])) {
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
                preserveScroll: true,
            },
        );
    };

    const handleTogglePin = async () => {
        const isCurrentlyPinned = topic.isPinned;
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
                    router.reload();
                },
            },
        );
    };

    const handleToggleLock = async () => {
        const isCurrentlyLocked = topic.isLocked;
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
                {can('pin_topics') && (
                    <DropdownMenuItem onClick={handleTogglePin} disabled={pinLoading}>
                        {topic.isPinned ? (
                            <>
                                <PinOff className="mr-2 size-4" />
                                Unpin Topic
                            </>
                        ) : (
                            <>
                                <Pin className="mr-2 size-4" />
                                Pin Topic
                            </>
                        )}
                    </DropdownMenuItem>
                )}

                {can('lock_topics') && (
                    <DropdownMenuItem onClick={handleToggleLock} disabled={lockLoading}>
                        {topic.isLocked ? (
                            <>
                                <LockOpen className="mr-2 size-4" />
                                Unlock Topic
                            </>
                        ) : (
                            <>
                                <Lock className="mr-2 size-4" />
                                Lock Topic
                            </>
                        )}
                    </DropdownMenuItem>
                )}

                {topic.permissions.canDelete && (
                    <DropdownMenuItem onClick={handleDeleteTopic} className="text-destructive focus:text-destructive">
                        <Trash className="mr-2 size-4 text-destructive" />
                        Delete Topic
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
