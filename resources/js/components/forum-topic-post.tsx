import EmojiReactions from '@/components/emoji-reactions';
import ForumUserInfo from '@/components/forum-user-info';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Card, CardContent } from '@/components/ui/card';
import type { Post } from '@/types';
import { formatDistanceToNow } from 'date-fns';

interface ForumTopicPostProps {
    post: Post;
    index: number;
}

export default function ForumTopicPost({ post, index }: ForumTopicPostProps) {
    return (
        <Card data-post>
            <CardContent className="px-6 py-0 md:py-6">
                <div className="flex flex-col gap-4 md:flex-row">
                    <div className="flex min-w-0 flex-col items-start gap-2 md:items-center">
                        <ForumUserInfo user={post.author} isAuthor={index === 0} />
                    </div>

                    <div className="min-w-0 flex-1">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="text-sm text-muted-foreground">
                                Posted {formatDistanceToNow(new Date(post.created_at), { addSuffix: true })}
                            </div>
                        </div>

                        <div className="prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: post.content }} />

                        <div className="mt-4 border-t border-muted pt-2">
                            <div className="mt-2">
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
