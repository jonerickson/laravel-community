import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Comment, Post } from '@/types';
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
            const url = post ? route('posts.like', { post: post.slug }) : route('comments.like', { comment: comment?.id });

            const response = await axios.post(url, { emoji });

            if (response.data.success) {
                setReactions(response.data.likes_summary || []);
                setCurrentUserReactions(response.data.user_reactions || []);
            }
        } catch (error) {
            console.error('Error toggling reaction:', error);

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

                    return (
                        <ToggleGroupItem
                            key={emoji}
                            value={emoji}
                            className={`px-2 py-1 text-sm transition-all hover:scale-105 ${hasReactions || isActive ? 'opacity-100' : 'opacity-60'}`}
                            title={
                                reaction?.users.length
                                    ? `${reaction.users.join(', ')}${reaction.users.length > 3 ? ' and others' : ''}`
                                    : `React with ${emoji}`
                            }
                        >
                            <span className="mr-1">{emoji}</span>
                            {hasReactions && <span className="text-xs font-medium">{count}</span>}
                        </ToggleGroupItem>
                    );
                })}
            </ToggleGroup>
        </div>
    );
}
