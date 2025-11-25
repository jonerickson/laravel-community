import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { MessageSquare, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface ForumSelectionDialogProps {
    forums: App.Data.ForumData[];
    isOpen: boolean;
    onClose: () => void;
    onSelect: (forum: App.Data.ForumData) => void;
    title: string;
    description: string;
}

export default function ForumSelectionDialog({ forums, isOpen, onClose, onSelect, title, description }: ForumSelectionDialogProps) {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedIndex, setSelectedIndex] = useState(0);
    const inputRef = useRef<HTMLInputElement>(null);
    const buttonRefs = useRef<(HTMLButtonElement | null)[]>([]);

    const handleForumSelect = (forum: App.Data.ForumData) => {
        onClose();
        onSelect(forum);
    };

    const filteredForums = forums.filter(
        (forum) =>
            forum.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (forum.description?.toLowerCase().includes(searchTerm.toLowerCase()) ?? false),
    );

    useEffect(() => {
        setSelectedIndex(0);
    }, [searchTerm]);

    useEffect(() => {
        if (isOpen && inputRef.current) {
            inputRef.current.focus();
        }
    }, [isOpen]);

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (filteredForums.length === 0) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                setSelectedIndex((prev) => (prev + 1) % filteredForums.length);
                break;
            case 'ArrowUp':
                e.preventDefault();
                setSelectedIndex((prev) => (prev - 1 + filteredForums.length) % filteredForums.length);
                break;
            case 'Tab':
                if (!e.shiftKey) {
                    e.preventDefault();
                    setSelectedIndex((prev) => (prev + 1) % filteredForums.length);
                } else {
                    e.preventDefault();
                    setSelectedIndex((prev) => (prev - 1 + filteredForums.length) % filteredForums.length);
                }
                break;
            case 'Enter':
                e.preventDefault();
                if (filteredForums[selectedIndex]) {
                    handleForumSelect(filteredForums[selectedIndex]);
                }
                break;
            case 'Escape':
                e.preventDefault();
                onClose();
                break;
        }
    };

    useEffect(() => {
        if (buttonRefs.current[selectedIndex]) {
            buttonRefs.current[selectedIndex]?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
            });
        }
    }, [selectedIndex]);

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <div className="relative">
                    <Input
                        ref={inputRef}
                        placeholder="Search forums..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        onKeyDown={handleKeyDown}
                    />
                </div>
                <ScrollArea className="max-h-[400px]">
                    <div className="space-y-2">
                        {filteredForums
                            .sort((a, b) => a.name.localeCompare(b.name))
                            .map((forum, index) => (
                                <Button
                                    key={forum.id}
                                    ref={(el) => {
                                        buttonRefs.current[index] = el;
                                    }}
                                    variant="ghost"
                                    className={`h-auto w-full justify-start p-4 text-left ${selectedIndex === index ? 'bg-accent' : ''}`}
                                    onClick={() => handleForumSelect(forum)}
                                    onMouseEnter={() => setSelectedIndex(index)}
                                >
                                    <div className="flex w-full items-start gap-3">
                                        <div
                                            className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-white"
                                            style={{ backgroundColor: forum.color }}
                                        >
                                            <MessageSquare className="size-5" />
                                        </div>
                                        <div className="flex min-w-0 flex-1 flex-col items-start">
                                            <div className="text-sm font-medium">{forum.name}</div>
                                            {forum.description && (
                                                <div className="text-left text-xs text-wrap break-words text-muted-foreground">
                                                    {forum.description}
                                                </div>
                                            )}
                                            <div className="mt-2 flex items-center gap-3 text-xs text-muted-foreground">
                                                <span>{forum.topicsCount || 0} topics</span>
                                                <span>{forum.postsCount || 0} posts</span>
                                            </div>
                                        </div>
                                    </div>
                                </Button>
                            ))}
                    </div>
                </ScrollArea>
                {filteredForums.length === 0 && forums.length > 0 && (
                    <div className="py-6 text-center text-muted-foreground">
                        <Search className="mx-auto mb-3 size-8 text-muted-foreground/50" />
                        <p className="text-sm">No forums found matching "{searchTerm}"</p>
                    </div>
                )}
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
