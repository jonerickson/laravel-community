import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { UserInfo } from '@/components/user-info';
import { Comment, Post } from '@/types';
import { useForm } from '@inertiajs/react';
import { MessageCircle, Reply } from 'lucide-react';
import { useState } from 'react';

interface BlogCommentsProps {
    post: Post;
}

interface CommentItemProps {
    comment: Comment;
    onReply: (parentId: number) => void;
    replyingTo: number | null;
}

function CommentItem({ comment, onReply, replyingTo }: CommentItemProps) {
    const commentDate = new Date(comment.created_at);
    const formattedDate = commentDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

    const { data, setData, post, processing, reset } = useForm({
        content: '',
        parent_id: comment.id,
        commentable_type: 'App\\Models\\Post',
        commentable_id: comment.commentable_id,
    });

    const handleReplySubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/comments', {
            onSuccess: () => {
                reset();
                onReply(0); // Close reply form
            },
        });
    };

    return (
        <div className="border-l-2 border-muted pl-4">
            <div className="mb-4 rounded-lg bg-muted/50 p-4">
                <div className="mb-2 flex items-center justify-between">
                    <div className="flex items-center gap-2">{comment.user && <UserInfo user={comment.user} showEmail={false} />}</div>
                    <time className="text-xs text-muted-foreground" dateTime={comment.created_at}>
                        {formattedDate}
                    </time>
                </div>

                <div className="mb-3 text-sm text-foreground">{comment.content}</div>

                <Button variant="ghost" size="sm" onClick={() => onReply(comment.id)} className="h-auto p-1 text-xs">
                    <Reply className="mr-1 h-3 w-3" />
                    Reply
                </Button>

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
                                {processing ? 'Posting...' : 'Post Reply'}
                            </Button>
                            <Button type="button" variant="outline" size="sm" onClick={() => onReply(0)}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                )}
            </div>

            {/* Nested replies */}
            {comment.replies && comment.replies.length > 0 && (
                <div className="ml-4 space-y-4">
                    {comment.replies.map((reply) => (
                        <CommentItem key={reply.id} comment={reply} onReply={onReply} replyingTo={replyingTo} />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function BlogComments({ post }: BlogCommentsProps) {
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
        commentable_type: 'App\\Models\\Post',
        commentable_id: post.id,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        submitComment('/comments', {
            onSuccess: () => {
                reset();
            },
        });
    };

    const approvedComments = post.comments?.filter((comment) => comment.is_approved && !comment.parent_id) || [];

    return (
        <div className="space-y-6">
            <div className="flex items-center gap-2">
                <MessageCircle className="h-5 w-5" />
                <h3 className="text-lg font-semibold">Comments ({post.comments_count || 0})</h3>
            </div>

            {/* Comment Form */}
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <Textarea
                        value={data.content}
                        onChange={(e) => setData('content', e.target.value)}
                        placeholder="Share your thoughts..."
                        className="min-h-[120px]"
                        required
                    />
                    {errors.content && <p className="mt-1 text-sm text-destructive">{errors.content}</p>}
                </div>
                <Button type="submit" disabled={processing}>
                    {processing ? 'Posting...' : 'Post Comment'}
                </Button>
            </form>

            {/* Comments List */}
            {approvedComments.length > 0 ? (
                <div className="space-y-6">
                    {approvedComments.map((comment) => (
                        <CommentItem key={comment.id} comment={comment} onReply={setReplyingTo} replyingTo={replyingTo} />
                    ))}
                </div>
            ) : (
                <div className="py-8 text-center text-muted-foreground">
                    <MessageCircle className="mx-auto mb-2 h-8 w-8" />
                    <p>No comments yet. Be the first to share your thoughts!</p>
                </div>
            )}
        </div>
    );
}
