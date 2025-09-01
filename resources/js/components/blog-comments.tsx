import EmojiReactions from '@/components/emoji-reactions';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { Textarea } from '@/components/ui/textarea';
import { UserInfo } from '@/components/user-info';
import { Comment, type PaginatedData, Post } from '@/types';
import { useForm } from '@inertiajs/react';
import { Edit, MessageCircle, Reply, Trash } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import usePermissions from '../hooks/use-permissions';

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
    const { can, cannot, hasAnyPermission } = usePermissions();
    const [isEditing, setIsEditing] = useState(false);
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

    const {
        data: editData,
        setData: setEditData,
        patch: updateComment,
        processing: editing,
        reset: resetEdit,
    } = useForm({
        content: comment.content,
    });

    const { delete: deleteComment, processing: deleting } = useForm();

    const handleReplySubmit = (e: React.FormEvent) => {
        if (cannot('create_comments')) {
            return;
        }

        e.preventDefault();
        submitComment(route('blog.comments.store', { post }), {
            onSuccess: () => {
                reset();
                onReply(0);
                toast.success('The reply has been successfully added.');
            },
            onError: (error) => toast.error(error.message || 'Unable to add reply. Please try again.'),
        });
    };

    const handleDelete = () => {
        if (cannot('delete_comments')) {
            return;
        }

        if (!confirm('Are you sure you want to delete this comment?')) {
            return;
        }

        deleteComment(route('blog.comments.destroy', { post, comment }), {
            onSuccess: () => toast.success('The comment has been successfully deleted.'),
            onError: (error) => toast.error(error.message || 'Unable to delete comment. Please try again.'),
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        if (cannot('update_comments')) {
            return;
        }

        e.preventDefault();
        updateComment(route('blog.comments.update', { post, comment }), {
            onSuccess: () => {
                setIsEditing(false);
                toast.success('The comment has been successfully updated.');
            },
            onError: (error) => toast.error(error.message || 'Unable to update comment. Please try again.'),
        });
    };

    const handleEditCancel = () => {
        setIsEditing(false);
        resetEdit();
    };

    return (
        <div className="space-y-6 border-l-2 border-muted pl-4" itemScope itemType="https://schema.org/Comment">
            <div className="flex flex-col gap-3 rounded-lg bg-muted/50 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        {comment.author && (
                            <span itemProp="author" itemScope itemType="https://schema.org/Person">
                                <UserInfo user={comment.author} showEmail={false} showGroups={true} />
                                <meta itemProp="name" content={comment.author.name} />
                            </span>
                        )}
                    </div>
                    <time className="text-xs text-muted-foreground" dateTime={comment.created_at} itemProp="datePublished">
                        {formattedDate}
                    </time>
                </div>

                {isEditing ? (
                    <form onSubmit={handleEditSubmit} className="space-y-3">
                        <Textarea
                            value={editData.content}
                            onChange={(e) => setEditData('content', e.target.value)}
                            className="min-h-[80px]"
                            required
                        />
                        <div className="flex gap-2">
                            <Button type="submit" size="sm" disabled={editing}>
                                {editing ? 'Saving...' : 'Save'}
                            </Button>
                            <Button type="button" variant="outline" size="sm" onClick={handleEditCancel} disabled={editing}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                ) : (
                    <>
                        <div className="text-sm text-foreground" itemProp="text">
                            {comment.content}
                        </div>

                        {hasAnyPermission(['create_comments', 'update_permissions', 'like_comments']) && (
                            <div className="flex items-center justify-between">
                                <div className="flex items-center">
                                    <Button variant="ghost" size="sm" onClick={() => onReply(comment.id)} className="h-auto p-1 text-xs">
                                        <Reply className="mr-1 h-3 w-3" />
                                        Reply
                                    </Button>
                                    {can('update_comments') && (
                                        <Button variant="ghost" size="sm" onClick={() => setIsEditing(true)} className="h-auto p-1 text-xs">
                                            <Edit className="mr-1 h-3 w-3" />
                                            Edit
                                        </Button>
                                    )}
                                    {can('delete_comments') && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={handleDelete}
                                            disabled={deleting}
                                            className="h-auto p-1 text-xs text-destructive hover:text-destructive"
                                        >
                                            <Trash className="mr-1 h-3 w-3" />
                                            {deleting ? 'Deleting...' : 'Delete'}
                                        </Button>
                                    )}
                                </div>
                                {can('like_comments') && (
                                    <EmojiReactions
                                        comment={comment}
                                        initialReactions={comment.likes_summary}
                                        userReactions={comment.user_reactions}
                                        className="ml-auto"
                                    />
                                )}
                            </div>
                        )}
                    </>
                )}

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
    const { can, cannot } = usePermissions();
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
        if (cannot('create_comments')) {
            return;
        }

        e.preventDefault();
        submitComment(route('blog.comments.store', { post }), {
            onSuccess: () => {
                reset();
                toast.success('The comment has been successfully added.');
            },
            onError: (error) => toast.error(error.message || 'Unable to add comment. Please try again.'),
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

            {can('create_comments') && (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Textarea
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            placeholder="Share your thoughts..."
                            className="min-h-[120px]"
                            required
                        />
                        <InputError message={errors.content} />
                    </div>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Posting...' : 'Post comment'}
                    </Button>
                </form>
            )}

            {approvedComments.length > 0 ? (
                <div className="space-y-6">
                    {approvedComments.map((comment) => (
                        <CommentItem key={comment.id} post={post} comment={comment} onReply={setReplyingTo} replyingTo={replyingTo} />
                    ))}

                    <Pagination pagination={commentsPagination} baseUrl={route('blog.show', { post: post.slug })} entityLabel="comment" />
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
