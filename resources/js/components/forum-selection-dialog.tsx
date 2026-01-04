import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { abbreviateNumber, pluralize } from '@/lib/utils';
import { ChevronRight, MessageSquare, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface ForumSelectionDialogProps {
    categories: App.Data.ForumCategoryData[];
    isOpen: boolean;
    onClose: () => void;
    onSelect: (forum: App.Data.ForumData) => void;
    title: string;
    description: string;
}

interface FlattenedItem {
    forum: App.Data.ForumData;
    depth: number;
    path: string[];
}

export default function ForumSelectionDialog({ categories, isOpen, onClose, onSelect, title, description }: ForumSelectionDialogProps) {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedIndex, setSelectedIndex] = useState(0);
    const inputRef = useRef<HTMLInputElement>(null);
    const buttonRefs = useRef<(HTMLButtonElement | null)[]>([]);

    const handleForumSelect = (forum: App.Data.ForumData) => {
        onClose();
        onSelect(forum);
    };

    const flattenForums = (categories: App.Data.ForumCategoryData[]): FlattenedItem[] => {
        const hasForumWriteAccess = (forum: App.Data.ForumData): boolean => {
            if (forum.forumPermissions?.canCreate) return true;
            return forum.children?.some((child) => hasForumWriteAccess(child)) ?? false;
        };

        const hasAnyWriteAccess = categories.some((category) => {
            if (!category.forumPermissions?.canCreate) return false;
            return category.forums?.some((forum) => hasForumWriteAccess(forum)) ?? false;
        });

        if (!hasAnyWriteAccess) return [];

        const items: FlattenedItem[] = [];

        const processForums = (forums: App.Data.ForumData[] | undefined, path: string[], depth: number) => {
            if (!forums) return;

            forums.forEach((forum) => {
                if (!forum.forumPermissions?.canCreate) return;

                items.push({
                    forum,
                    depth,
                    path: [...path],
                });

                if (forum.children && forum.children.length > 0) {
                    processForums(forum.children, [...path, forum.name], depth + 1);
                }
            });
        };

        categories.forEach((category) => {
            if (category.forumPermissions?.canCreate && category.forums && category.forums.length > 0) {
                processForums(category.forums, [category.name], 0);
            }
        });

        return items;
    };

    const allForums = flattenForums(categories);

    const filteredForums = allForums.filter(
        (item) =>
            item.forum.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (item.forum.description?.toLowerCase().includes(searchTerm.toLowerCase()) ?? false) ||
            item.path.some((p) => p.toLowerCase().includes(searchTerm.toLowerCase())),
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
                    handleForumSelect(filteredForums[selectedIndex].forum);
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
                    <div className="space-y-1">
                        {filteredForums.map((item, index) => (
                            <Button
                                key={item.forum.id}
                                ref={(el) => {
                                    buttonRefs.current[index] = el;
                                }}
                                variant="ghost"
                                className={`h-auto w-full justify-start text-left ${selectedIndex === index ? 'bg-accent' : ''}`}
                                onClick={() => handleForumSelect(item.forum)}
                                onMouseEnter={() => setSelectedIndex(index)}
                                style={{ paddingLeft: `${1 + item.depth * 1.5}rem` }}
                            >
                                <div className="flex w-full items-start gap-3 py-3">
                                    <div
                                        className="flex size-8 shrink-0 items-center justify-center rounded-lg text-white"
                                        style={{ backgroundColor: item.forum.color }}
                                    >
                                        <MessageSquare className="size-4" />
                                    </div>
                                    <div className="flex min-w-0 flex-1 flex-col items-start gap-1">
                                        {item.path.length > 0 && (
                                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                {item.path.map((pathItem, pathIndex) => (
                                                    <div key={pathIndex} className="flex items-center gap-1">
                                                        <span>{pathItem}</span>
                                                        {pathIndex < item.path.length - 1 && <ChevronRight className="size-3" />}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                        <div className="text-sm font-medium">{item.forum.name}</div>
                                        {item.forum.description && (
                                            <div className="text-left text-xs text-wrap break-words text-muted-foreground">
                                                {item.forum.description}
                                            </div>
                                        )}
                                        <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                            <span>
                                                {abbreviateNumber(item.forum.topicsCount || 0)} {pluralize('topic', item.forum.topicsCount || 0)}
                                            </span>
                                            <span>
                                                {abbreviateNumber(item.forum.postsCount || 0)} {pluralize('post', item.forum.postsCount || 0)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </Button>
                        ))}
                    </div>
                </ScrollArea>
                {filteredForums.length === 0 && allForums.length > 0 && (
                    <div className="py-6 text-center text-muted-foreground">
                        <Search className="mx-auto mb-3 size-8 text-muted-foreground/50" />
                        <p className="text-sm">No forums found matching "{searchTerm}"</p>
                    </div>
                )}
                {allForums.length === 0 && (
                    <div className="py-6 text-center text-muted-foreground">
                        <MessageSquare className="mx-auto mb-3 size-8 text-muted-foreground/50" />
                        <p className="text-sm">No forums available</p>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
