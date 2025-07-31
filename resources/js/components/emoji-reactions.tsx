import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Comment, EmojiReactionResponse, Post } from '@/types';
import { ApiError, apiRequest } from '@/utils/api';
import axios from 'axios';
import { useState } from 'react';

interface EmojiReaction {
    emoji: string;
    count: number;
    users: string[];
}

interface EmojiReactionsProps {
    post?: Post;
    comment?: Comment;
    initialReactions?: EmojiReaction[];
    userReactions?: string[];
    className?: string;
}

const AVAILABLE_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '😡'];

export default function EmojiReactions({ post, comment, initialReactions = [], userReactions = [], className = '' }: EmojiReactionsProps) {
    const [reactions, setReactions] = useState<EmojiReaction[]>(initialReactions);
    const [currentUserReactions, setCurrentUserReactions] = useState<string[]>(userReactions);
    const [loading, setLoading] = useState(false);

    const handleEmojiToggle = async (emoji: string) => {
        if (loading) return;

        setLoading(true);

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

        try {
            const data = await apiRequest<EmojiReactionResponse>(
                axios.post(route('like'), {
                    type: post ? 'post' : 'comment',
                    id: post ? post.id : comment?.id,
                    emoji,
                }),
            );

            setReactions(data.likes_summary || []);
            setCurrentUserReactions(data.user_reactions || []);
        } catch (error) {
            console.error('Error toggling reaction:', error);
            const apiError = error as ApiError;
            console.error('API Error:', apiError.message);

            setReactions(initialReactions);
            setCurrentUserReactions(userReactions);
        } finally {
            setLoading(false);
        }
    };

    const reactionMap = reactions.reduce(
        (acc, reaction) => {
            acc[reaction.emoji] = reaction;
            return acc;
        },
        {} as Record<string, EmojiReaction>,
    );

    return (
        <div className={`flex items-center gap-2 ${className}`}>
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
                variant="outline"
                size="sm"
                disabled={loading}
                className="gap-0"
            >
                {AVAILABLE_EMOJIS.map((emoji) => {
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
                                    {displayUsers.map((user, index) => (
                                        <div key={index}>{user}</div>
                                    ))}
                                    {remainingCount > 0 && <div className="text-muted-foreground">+{remainingCount} more</div>}
                                </div>
                            </div>
                        );
                    };

                    return (
                        <Tooltip key={emoji}>
                            <TooltipTrigger asChild>
                                <ToggleGroupItem
                                    value={emoji}
                                    className={`px-2 py-1 text-sm transition-all hover:scale-105 ${hasReactions || isActive ? 'opacity-100' : 'opacity-60'}`}
                                >
                                    <span className="mr-1">{emoji}</span>
                                    {hasReactions && <span className="text-xs font-medium">{count}</span>}
                                </ToggleGroupItem>
                            </TooltipTrigger>
                            <TooltipContent className="max-w-xs">{renderTooltipContent()}</TooltipContent>
                        </Tooltip>
                    );
                })}
            </ToggleGroup>
        </div>
    );
}
