import EmojiReactions from '@/components/emoji-reactions';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { Textarea } from '@/components/ui/textarea';
import { UserInfo } from '@/components/user-info';
import { Comment, type PaginatedData, Post } from '@/types';
import { useForm } from '@inertiajs/react';
import { MessageCircle, Reply } from 'lucide-react';
import { useState } from 'react';

interface BlogCommentsProps {
    post: Post;
    comments: Comment[];
    commentsPagination: PaginatedData;
}

interface CommentItemProps {
    post: Post;
    comment: Comment;
    onReply: (parentId: number) => void;
    replyingTo: number | null;
}

function CommentItem({ post, comment, onReply, replyingTo }: CommentItemProps) {
    const commentDate = new Date(comment.created_at);
    const formattedDate = commentDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

    const {
        data,
        setData,
        post: submitComment,
        processing,
        reset,
    } = useForm({
        content: '',
        parent_id: comment.id,
    });

    const handleReplySubmit = (e: React.FormEvent) => {
        e.preventDefault();
        submitComment(route('blog.comments.store', { post }), {
            onSuccess: () => {
                reset();
                onReply(0);
            },
        });
    };

    return (
        <div className="border-l-2 border-muted pl-4">
            <div className="mb-4 rounded-lg bg-muted/50 p-4">
                <div className="mb-2 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        {comment.author && <UserInfo user={comment.author} showEmail={false} showGroups={true} />}
                    </div>
                    <time className="text-xs text-muted-foreground" dateTime={comment.created_at}>
                        {formattedDate}
                    </time>
                </div>

                <div className="mb-3 text-sm text-foreground">{comment.content}</div>

                <div className="flex items-center justify-between">
                    <Button variant="ghost" size="sm" onClick={() => onReply(comment.id)} className="h-auto p-1 text-xs">
                        <Reply className="mr-1 h-3 w-3" />
                        Reply
                    </Button>
                    <EmojiReactions
                        comment={comment}
                        initialReactions={comment.likes_summary}
                        userReactions={comment.user_reactions}
                        className="ml-auto"
                    />
                </div>

                {replyingTo === comment.id && (
                    <form onSubmit={handleReplySubmit} className="mt-3 space-y-3">
                        <Textarea
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            placeholder="Write a reply..."
                            className="min-h-[80px]"
                            required
                        />
                        <div className="flex gap-2">
                            <Button type="submit" size="sm" disabled={processing}>
                                {processing ? 'Posting...' : 'Post reply'}
                            </Button>
                            <Button type="button" variant="outline" size="sm" onClick={() => onReply(0)} disabled={processing}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                )}
            </div>

            {comment.replies && comment.replies.length > 0 && (
                <div className="ml-4 space-y-4">
                    {comment.replies.map((reply) => (
                        <CommentItem key={reply.id} post={post} comment={reply} onReply={onReply} replyingTo={replyingTo} />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function BlogComments({ post, comments, commentsPagination }: BlogCommentsProps) {
    const [replyingTo, setReplyingTo] = useState<number | null>(null);
    const {
        data,
        setData,
        post: submitComment,
        processing,
        reset,
        errors,
    } = useForm({
        content: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        submitComment(route('blog.comments.store', { post }), {
            onSuccess: () => {
                reset();
            },
        });
    };

    const approvedComments = comments.filter((comment) => comment.is_approved && !comment.parent_id) || [];

    if (!post.comments_enabled) {
        return (
            <div className="space-y-6">
                <div className="flex items-center gap-2">
                    <MessageCircle className="h-5 w-5" />
                    <HeadingSmall title="Comments" />
                </div>
                <div className="py-8 text-center text-muted-foreground">
                    <MessageCircle className="mx-auto mb-2 h-8 w-8" />
                    <p>Comments are disabled for this post.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center gap-2">
                <MessageCircle className="h-5 w-5" />
                <HeadingSmall title={`Comments (${comments.length || 0})`} />
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Textarea
                        value={data.content}
                        onChange={(e) => setData('content', e.target.value)}
                        placeholder="Share your thoughts..."
                        className="min-h-[120px]"
                        required
                    />
                    {errors.content && <InputError message={errors.content} />}
                </div>
                <Button type="submit" disabled={processing}>
                    {processing ? 'Posting...' : 'Post comment'}
                </Button>
            </form>

            {approvedComments.length > 0 ? (
                <div className="space-y-6">
                    {approvedComments.map((comment) => (
                        <CommentItem key={comment.id} post={post} comment={comment} onReply={setReplyingTo} replyingTo={replyingTo} />
                    ))}

                    <Pagination pagination={commentsPagination} baseUrl={''} entityLabel="comment" />
                </div>
            ) : (
                <div className="py-8 text-center text-muted-foreground">
                    <MessageCircle className="mx-auto mb-2 h-8 w-8" />
                    <p className="text-sm font-medium">No comments yet. Be the first to share your thoughts!</p>
                </div>
            )}
        </div>
    );
}
