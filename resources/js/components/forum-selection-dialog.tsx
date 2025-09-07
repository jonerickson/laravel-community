import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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
        router.get(route('forums.topics.create', { forum: forum.slug }));
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Select a Forum</DialogTitle>
                    <DialogDescription>Choose which forum you'd like to create a new topic in.</DialogDescription>
                </DialogHeader>
                <ScrollArea className="max-h-[400px]">
                    <div className="space-y-2">
                        {forums.map((forum) => (
                            <Button
                                key={forum.id}
                                variant="ghost"
                                className="h-auto w-full justify-start p-4 text-left"
                                onClick={() => handleForumSelect(forum)}
                            >
                                <div className="flex w-full items-start gap-3">
                                    <div
                                        className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-white"
                                        style={{ backgroundColor: forum.color }}
                                    >
                                        <MessageSquare className="h-5 w-5" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="text-sm font-medium">{forum.name}</div>
                                        {forum.description && <div className="mt-1 truncate text-xs text-muted-foreground">{forum.description}</div>}
                                        <div className="mt-2 flex items-center gap-3 text-xs text-muted-foreground">
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
                    <div className="py-6 text-center text-muted-foreground">
                        <MessageSquare className="mx-auto mb-3 size-8 text-muted-foreground/50" />
                        <p className="text-sm">No forums available</p>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
