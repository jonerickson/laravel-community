import EmojiReactions from '@/components/emoji-reactions';
import ForumTopicPostModerationMenu from '@/components/forum-topic-post-moderation-menu';
import ForumUserInfo from '@/components/forum-user-info';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { Forum, Post, SharedData, Topic } from '@/types';
import { usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { EyeOff, Flag } from 'lucide-react';

interface ForumTopicPostProps {
    post: Post;
    index: number;
    forum: Forum;
    topic: Topic;
}

export default function ForumTopicPost({ post, index, forum, topic }: ForumTopicPostProps) {
    const { auth } = usePage<SharedData>().props;

    // Determine if post should be hidden (reported but not for admins)
    const isHiddenForUser = post.is_reported && !auth.isAdmin;

    // Determine card styling based on post status
    const getCardClassName = () => {
        if (!post.is_published) return 'border-warning-foreground bg-warning';
        if (post.is_reported && auth.isAdmin) return 'border-destructive-foreground bg-destructive/10';
        return '';
    };

    // Don't render reported posts for non-admin users
    if (isHiddenForUser) {
        return null;
    }

    return (
        <Card data-post className={getCardClassName()}>
            <CardContent className="px-6 py-0 md:py-6">
                <div className="flex flex-col gap-4 md:flex-row">
                    <div className="flex min-w-0 flex-col items-start gap-2 md:items-center">
                        <ForumUserInfo user={post.author} isAuthor={index === 0} />
                    </div>

                    <div className="min-w-0 flex-1">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    Posted {formatDistanceToNow(new Date(post.created_at), { addSuffix: true })}
                                </span>
                                {!post.is_published && (
                                    <Badge variant="destructive">
                                        <EyeOff className="mr-1 h-3 w-3" />
                                        Unpublished
                                    </Badge>
                                )}
                                {post.is_reported && auth.isAdmin && (
                                    <Badge variant="destructive">
                                        <Flag className="mr-1 h-3 w-3" />
                                        Reported {post.report_count && post.report_count > 1 ? `(${post.report_count})` : ''}
                                    </Badge>
                                )}
                            </div>
                            <ForumTopicPostModerationMenu post={post} forum={forum} topic={topic} />
                        </div>

                        <div className="prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: post.content }} />

                        <div className="mt-4 border-t border-muted pt-2">
                            {post.author?.signature && (
                                <div className="mt-2 text-xs text-muted-foreground">
                                    <div
                                        className="prose prose-xs max-w-none italic [&>*]:text-muted-foreground"
                                        dangerouslySetInnerHTML={{ __html: post.author.signature }}
                                    />
                                </div>
                            )}

                            <div className="mt-4">
                                <EmojiReactions
                                    post={post}
                                    initialReactions={post.likes_summary}
                                    userReactions={post.user_reactions}
                                    className="mb-2"
                                />
                            </div>
                        </div>

                        {post.comments && post.comments.length > 0 && (
                            <div className="mt-6 border-t pt-4">
                                <div className="mb-3 text-sm font-medium">Comments</div>
                                <div className="space-y-3">
                                    {post.comments.map((comment) => (
                                        <div key={comment.id} className="flex gap-3 rounded-lg bg-muted/50 p-3">
                                            <Avatar className="h-8 w-8">
                                                <AvatarFallback className="text-xs">{comment.user?.name.charAt(0).toUpperCase()}</AvatarFallback>
                                            </Avatar>
                                            <div className="flex-1">
                                                <div className="mb-1 flex items-center gap-2">
                                                    <span className="text-sm font-medium">{comment.user?.name}</span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {formatDistanceToNow(new Date(comment.created_at), { addSuffix: true })}
                                                    </span>
                                                </div>
                                                <p className="text-sm">{comment.content}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
