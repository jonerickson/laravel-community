import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import type { Forum } from '@/types';
import { router } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';

interface ForumSelectionDialogProps {
    forums: Forum[];
    isOpen: boolean;
    onClose: () => void;
}

export default function ForumSelectionDialog({ forums, isOpen, onClose }: ForumSelectionDialogProps) {
    const handleForumSelect = (forum: Forum) => {
        onClose();
        router.visit(`/forums/${forum.slug}/create`);
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Select a Forum</DialogTitle>
                    <DialogDescription>
                        Choose which forum you'd like to create a new topic in.
                    </DialogDescription>
                </DialogHeader>
                <ScrollArea className="max-h-[400px]">
                    <div className="space-y-2">
                        {forums.map((forum) => (
                            <Button
                                key={forum.id}
                                variant="ghost"
                                className="w-full h-auto p-4 justify-start text-left"
                                onClick={() => handleForumSelect(forum)}
                            >
                                <div className="flex items-start gap-3 w-full">
                                    <div
                                        className="flex h-10 w-10 items-center justify-center rounded-lg text-white flex-shrink-0"
                                        style={{ backgroundColor: forum.color }}
                                    >
                                        <MessageSquare className="h-5 w-5" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="font-medium text-sm">{forum.name}</div>
                                        {forum.description && (
                                            <div className="text-xs text-muted-foreground mt-1 truncate">
                                                {forum.description}
                                            </div>
                                        )}
                                        <div className="flex items-center gap-3 text-xs text-muted-foreground mt-2">
                                            <span>{forum.topics_count || 0} topics</span>
                                            <span>{forum.posts_count || 0} posts</span>
                                        </div>
                                    </div>
                                </div>
                            </Button>
                        ))}
                    </div>
                </ScrollArea>
                {forums.length === 0 && (
                    <div className="text-center py-6 text-muted-foreground">
                        <MessageSquare className="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p className="text-sm">No forums available</p>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}