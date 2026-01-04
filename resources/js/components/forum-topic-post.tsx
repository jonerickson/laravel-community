import EmojiReactions from '@/components/emoji-reactions';
import ForumTopicPostModerationMenu from '@/components/forum-topic-post-moderation-menu';
import ForumUserInfo from '@/components/forum-user-info';
import { ReportDialog } from '@/components/report-dialog';
import RichEditorContent from '@/components/rich-editor-content';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { stripCharacters } from '@/utils/truncate';
import { formatDistanceToNow } from 'date-fns';
import { EyeOff, Flag, Pin, Quote, ThumbsDown } from 'lucide-react';

interface ForumTopicPostProps {
    post: App.Data.PostData;
    index: number;
    forum: App.Data.ForumData;
    topic: App.Data.TopicData;
    onQuote?: (content: string, authorName: string) => void;
}

export default function ForumTopicPost({ post, index, forum, topic, onQuote }: ForumTopicPostProps) {
    const isHiddenForUser = (post.isReported || !post.isPublished || !post.isApproved) && !forum.forumPermissions.canModerate;

    if (isHiddenForUser) {
        return null;
    }

    const getCardClassName = () => {
        if (post.isPinned) return 'border-info/50 ring-[3px] ring-inset ring-info/20';
        if (!post.isPublished || !post.isApproved) return 'border-warning/50 ring-[3px] ring-inset ring-warning/20';
        if (post.isReported) return 'border-destructive/50 ring-[3px] ring-inset ring-destructive/20';
        return '';
    };

    const handleQuote = () => {
        if (onQuote && post.author?.name) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = post.content;
            const cleanContent = tempDiv.textContent || tempDiv.innerText || '';
            onQuote(cleanContent, post.author.name);
        }
    };

    const hasSignature = post.author && post.author.signature !== '' && stripCharacters(post.author.signature || '').length > 0;

    return (
        <article id={post.id as unknown as string} itemScope itemType="https://schema.org/Comment">
            <Card data-post className={getCardClassName()}>
                <CardContent className="px-6 py-0 md:py-6">
                    <div className="flex flex-col gap-4 md:flex-row">
                        <div
                            className="flex min-w-0 flex-row items-start justify-between gap-2 md:w-1/4 md:flex-col md:items-center md:justify-start lg:w-1/6"
                            itemProp="author"
                            itemScope
                            itemType="https://schema.org/Person"
                        >
                            <div>
                                <ForumUserInfo user={post.author} isAuthor={index === 0} dateTime={post.createdAt || undefined} />
                                <meta itemProp="name" content={post.author?.name || undefined} />
                            </div>
                            <div className="md:hidden">
                                <ForumTopicPostModerationMenu post={post} forum={forum} topic={topic} />
                            </div>
                        </div>

                        <div className="min-w-0 flex-1">
                            <div className="mb-4 hidden items-center justify-between md:flex">
                                <div className="flex items-center gap-2">
                                    <time className="text-sm text-muted-foreground" itemProp="dateCreated" dateTime={post.createdAt || undefined}>
                                        Posted {post.createdAt ? formatDistanceToNow(new Date(post.createdAt), { addSuffix: true }) : 'N/A'}
                                    </time>
                                    {post.isPinned && (
                                        <Badge variant="info">
                                            <Pin className="mr-1 size-3" />
                                            Pinned
                                        </Badge>
                                    )}
                                    {!post.isPublished && (
                                        <Badge variant="warning">
                                            <EyeOff className="mr-1 size-3" />
                                            Unpublished
                                        </Badge>
                                    )}
                                    {!post.isApproved && (
                                        <Badge variant="warning">
                                            <ThumbsDown className="mr-1 size-3" />
                                            Pending Approval
                                        </Badge>
                                    )}
                                    {post.isReported && (
                                        <Badge variant="destructive">
                                            <Flag className="mr-1 size-3" />
                                            Reported {post.reportCount && post.reportCount > 1 ? `(${post.reportCount})` : ''}
                                        </Badge>
                                    )}
                                </div>
                                <ForumTopicPostModerationMenu post={post} forum={forum} topic={topic} />
                            </div>

                            <RichEditorContent itemProp="text" content={post.content} />

                            <div
                                className={cn('pt-2', {
                                    'mt-4 border-t border-muted': hasSignature,
                                })}
                            >
                                {hasSignature && (
                                    <div className="mt-2 text-xs text-muted-foreground">
                                        <RichEditorContent content={post.author.signature || ''} />
                                    </div>
                                )}

                                <div className="mt-4 flex items-start justify-between rounded-sm bg-muted p-2">
                                    <div className="flex gap-2">
                                        {forum.forumPermissions.canReport && !post.isReported && (
                                            <ReportDialog reportableType="App\Models\Post" reportableId={post.id} />
                                        )}
                                        {forum.forumPermissions.canReply && (
                                            <Button variant="ghost" size="sm" className="h-8 px-3 text-muted-foreground" onClick={handleQuote}>
                                                <Quote className="mr-1 size-3" />
                                                Quote
                                            </Button>
                                        )}
                                    </div>
                                    <EmojiReactions post={post} initialReactions={post.likesSummary} userReactions={post.userReactions} />
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
                                                            {comment.createdAt
                                                                ? formatDistanceToNow(new Date(comment.createdAt), { addSuffix: true })
                                                                : 'N/A'}
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
        </article>
    );
}
