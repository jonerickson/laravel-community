import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useApiRequest } from '@/hooks/use-api-request';
import { MoreHorizontal } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface EmojiReactionsProps {
    post?: App.Data.PostData;
    comment?: App.Data.CommentData;
    initialReactions?: App.Data.LikeData[];
    userReactions?: string[];
    className?: string;
}

const AVAILABLE_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '😡'];

export default function EmojiReactions({ post, comment, initialReactions = [], userReactions = [], className = '' }: EmojiReactionsProps) {
    const [reactions, setReactions] = useState<App.Data.LikeData[]>(initialReactions);
    const [currentUserReactions, setCurrentUserReactions] = useState<string[]>(userReactions);
    const { loading, execute } = useApiRequest<App.Data.LikeSummaryData>();

    const handleEmojiToggle = async (emoji: string) => {
        if (loading) return;

        const wasActive = currentUserReactions.includes(emoji);
        let newUserReactions: string[];

        if (wasActive) {
            newUserReactions = currentUserReactions.filter((r) => r !== emoji);
        } else {
            newUserReactions = [...currentUserReactions, emoji];
        }

        setCurrentUserReactions(newUserReactions);

        const updatedReactions = reactions.map((reaction) => {
            if (reaction.emoji === emoji) {
                return {
                    ...reaction,
                    count: wasActive ? reaction.count - 1 : reaction.count + 1,
                };
            }
            return reaction;
        });

        if (!reactions.find((r) => r.emoji === emoji) && !wasActive) {
            updatedReactions.push({
                emoji,
                count: 1,
                users: [],
            });
        }

        setReactions(updatedReactions.filter((r) => r.count > 0));

        await execute(
            {
                url: route('api.like'),
                method: 'POST',
                data: {
                    type: post ? 'post' : 'comment',
                    id: post ? post.id : comment?.id,
                    emoji,
                },
            },
            {
                onSuccess: (responseData) => {
                    setReactions(responseData.likesSummary || []);
                    setCurrentUserReactions(responseData.userReactions || []);
                },
                onError: () => {
                    setReactions(initialReactions);
                    setCurrentUserReactions(userReactions);
                },
            },
        );
    };

    const reactionMap = reactions.reduce(
        (acc, reaction) => {
            acc[reaction.emoji] = reaction;
            return acc;
        },
        {} as Record<string, App.Data.LikeData>,
    );

    const renderEmojiButton = (emoji: string, showTooltip = true) => {
        const reaction = reactionMap[emoji];
        const count = reaction?.count || 0;
        const hasReactions = count > 0;
        const isActive = currentUserReactions.includes(emoji);

        const renderTooltipContent = () => {
            if (!reaction?.users.length) {
                return <p>React with {emoji}</p>;
            }

            const displayUsers = reaction.users.slice(0, 5);
            const remainingCount = reaction.users.length - displayUsers.length;

            return (
                <div className="space-y-1">
                    <div className="text-xs">
                        {displayUsers.map((user: string, index: number) => (
                            <div key={index}>{user}</div>
                        ))}
                        {remainingCount > 0 && <div className="text-muted-foreground">+{remainingCount} more</div>}
                    </div>
                </div>
            );
        };

        const button = (
            <ToggleGroupItem
                value={emoji}
                className={`px-2 py-1 text-sm transition-all hover:scale-105 ${hasReactions || isActive ? 'opacity-100' : 'opacity-60'}`}
            >
                <span className="mr-1">{emoji}</span>
                {hasReactions && <span className="text-xs font-medium">{count}</span>}
            </ToggleGroupItem>
        );

        if (!showTooltip) {
            return button;
        }

        return (
            <Tooltip key={emoji}>
                <TooltipTrigger asChild>{button}</TooltipTrigger>
                <TooltipContent className="max-w-xs">{renderTooltipContent()}</TooltipContent>
            </Tooltip>
        );
    };

    return (
        <div className={`flex items-center gap-2 ${className}`}>
            <div className="hidden md:flex">
                <ToggleGroup
                    type="multiple"
                    value={currentUserReactions}
                    onValueChange={(newValues) => {
                        const added = newValues.find((val) => !currentUserReactions.includes(val));
                        const removed = currentUserReactions.find((val) => !newValues.includes(val));

                        if (added) {
                            handleEmojiToggle(added);
                        } else if (removed) {
                            handleEmojiToggle(removed);
                        }
                    }}
                    variant="default"
                    size="sm"
                    disabled={loading}
                    className="gap-0"
                >
                    {AVAILABLE_EMOJIS.map((emoji) => renderEmojiButton(emoji))}
                </ToggleGroup>
            </div>

            <div className="flex items-center gap-1 md:hidden">
                <ToggleGroup
                    type="multiple"
                    value={currentUserReactions}
                    onValueChange={(newValues) => {
                        const added = newValues.find((val) => !currentUserReactions.includes(val));
                        const removed = currentUserReactions.find((val) => !newValues.includes(val));

                        if (added) {
                            handleEmojiToggle(added);
                        } else if (removed) {
                            handleEmojiToggle(removed);
                        }
                    }}
                    variant="default"
                    size="sm"
                    disabled={loading}
                    className="gap-0"
                ></ToggleGroup>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm" className="px-2 py-1" disabled={loading}>
                            <MoreHorizontal className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-32">
                        {AVAILABLE_EMOJIS.map((emoji) => {
                            const reaction = reactionMap[emoji];
                            const count = reaction?.count || 0;
                            const hasReactions = count > 0;
                            const isActive = currentUserReactions.includes(emoji);

                            return (
                                <DropdownMenuItem key={emoji} onClick={() => handleEmojiToggle(emoji)} className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <span>{emoji}</span>
                                        {hasReactions && <span className="rounded bg-muted px-1.5 py-0.5 text-xs font-medium">{count}</span>}
                                    </div>
                                    {isActive && <span className="text-xs text-primary">✓</span>}
                                </DropdownMenuItem>
                            );
                        })}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}
