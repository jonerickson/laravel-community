import ForumSelectionDialog from '@/components/forum-selection-dialog';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useApiRequest } from '@/hooks/use-api-request';
import { router, useForm } from '@inertiajs/react';
import { ArrowLeftRight, Lock, LockOpen, MoreHorizontal, Pin, PinOff, Trash } from 'lucide-react';
import { useState } from 'react';

interface ForumTopicModerationMenuProps {
    topic: App.Data.TopicData;
    forum: App.Data.ForumData;
    categories: App.Data.ForumCategoryData[];
}

export default function ForumTopicModerationMenu({ topic, forum, categories }: ForumTopicModerationMenuProps) {
    const { delete: deleteTopic } = useForm();
    const { execute: pinTopic, loading: pinLoading } = useApiRequest();
    const { execute: lockTopic, loading: lockLoading } = useApiRequest();
    const { execute: moveTopic, loading: moveLoading } = useApiRequest();
    const [showMoveDialog, setShowMoveDialog] = useState(false);

    if (
        !forum.forumPermissions.canPin &&
        !forum.forumPermissions.canLock &&
        !forum.forumPermissions.canMove &&
        !forum.forumPermissions.canDelete &&
        !topic.policyPermissions.canDelete
    ) {
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
                    type: 'topic',
                    id: topic.id,
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
                    type: 'topic',
                    id: topic.id,
                },
            },
            {
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    };

    const handleOpenMoveDialog = () => {
        setShowMoveDialog(true);
    };

    const handleMoveTopic = async (targetForum: App.Data.ForumData) => {
        setShowMoveDialog(false);
        await moveTopic(
            {
                url: route('api.forums.topics.update', { topic: topic.slug }),
                method: 'PUT',
                data: {
                    topic_id: topic.id,
                    target_forum_id: targetForum.id,
                },
            },
            {
                onSuccess: () => {
                    router.visit(route('forums.topics.show', { forum: targetForum.slug, topic: topic.slug }));
                },
            },
        );
    };

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                        <MoreHorizontal className="size-4" />
                        <span className="sr-only">Open menu</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    {forum.forumPermissions.canPin && (
                        <DropdownMenuItem onClick={handleTogglePin} disabled={pinLoading}>
                            {topic.isPinned ? (
                                <>
                                    <PinOff />
                                    Unpin Topic
                                </>
                            ) : (
                                <>
                                    <Pin />
                                    Pin Topic
                                </>
                            )}
                        </DropdownMenuItem>
                    )}

                    {forum.forumPermissions.canLock && (
                        <DropdownMenuItem onClick={handleToggleLock} disabled={lockLoading}>
                            {topic.isLocked ? (
                                <>
                                    <LockOpen />
                                    Unlock Topic
                                </>
                            ) : (
                                <>
                                    <Lock />
                                    Lock Topic
                                </>
                            )}
                        </DropdownMenuItem>
                    )}

                    {forum.forumPermissions.canMove && (
                        <DropdownMenuItem onClick={handleOpenMoveDialog} disabled={moveLoading}>
                            <ArrowLeftRight />
                            Move Topic
                        </DropdownMenuItem>
                    )}

                    {(forum.forumPermissions.canDelete || topic.policyPermissions.canDelete) && (
                        <DropdownMenuItem onClick={handleDeleteTopic} className="text-destructive focus:text-destructive">
                            <Trash className="text-destructive" />
                            Delete Topic
                        </DropdownMenuItem>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>

            <ForumSelectionDialog
                categories={categories}
                isOpen={showMoveDialog}
                onClose={() => setShowMoveDialog(false)}
                onSelect={handleMoveTopic}
                title="Move topic"
                description="Choose which forum to move this topic to."
            />
        </>
    );
}
