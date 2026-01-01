import EmojiReactions from '@/components/emoji-reactions';
import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import RichEditorContent from '@/components/rich-editor-content';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { UserInfo } from '@/components/user-info';
import { cn } from '@/lib/utils';
import { InfiniteScroll, useForm } from '@inertiajs/react';
import { Edit, LoaderCircle, MessageCircle, Reply, Trash } from 'lucide-react';
import { useState } from 'react';

interface BlogCommentsProps {
    post: App.Data.PostData;
    comments: App.Data.PaginatedData<App.Data.CommentData>;
}

interface CommentItemProps {
    post: App.Data.PostData;
    comment: App.Data.CommentData;
    onReply: (parentId: number) => void;
    replyingTo: number | null;
}

function CommentItem({ post, comment, onReply, replyingTo }: CommentItemProps) {
    const [isEditing, setIsEditing] = useState(false);
    const commentDate = new Date(comment.createdAt || 'today');
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
        errors,
    } = useForm({
        content: comment.content,
    });

    const { delete: deleteComment, processing: deleting } = useForm();

    const handleReplySubmit = (e: React.FormEvent) => {
        e.preventDefault();

        submitComment(route('blog.comments.store', { post }), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onReply(0);
            },
        });
    };

    const handleDelete = () => {
        if (!comment.policyPermissions.canDelete) {
            return;
        }

        if (!confirm('Are you sure you want to delete this comment?')) {
            return;
        }

        deleteComment(route('blog.comments.destroy', { post, comment }), {
            preserveScroll: true,
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!comment.policyPermissions.canUpdate) {
            return;
        }

        updateComment(route('blog.comments.update', { post, comment }), {
            preserveScroll: true,
            onSuccess: () => {
                setIsEditing(false);
            },
        });
    };

    const handleEditCancel = () => {
        setIsEditing(false);
        resetEdit();
    };

    return (
        <div className="space-y-6 border-l-2 border-muted pl-4" itemScope itemType="https://schema.org/Comment">
            <div
                className={cn('relative flex flex-col gap-3 rounded-lg p-4', {
                    'bg-muted': comment.isApproved,
                    'border-2 border-warning bg-warning/10': !comment.isApproved,
                })}
            >
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-2">
                        {comment.author && (
                            <span itemProp="author" itemScope itemType="https://schema.org/Person">
                                <UserInfo user={comment.author} showEmail={false} showGroups={true} />
                                <meta itemProp="name" content={comment.author.name} />
                            </span>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        {!comment.isApproved && (
                            <span className="rounded-full bg-warning px-2 py-0.5 text-xs font-medium text-warning-foreground">Pending Approval</span>
                        )}
                        <time className="text-xs text-muted-foreground" dateTime={comment.createdAt || undefined} itemProp="datePublished">
                            {formattedDate}
                        </time>
                    </div>
                </div>

                {isEditing ? (
                    <form onSubmit={handleEditSubmit} className="space-y-3">
                        <div className="grid gap-2">
                            <Textarea
                                value={editData.content}
                                onChange={(e) => setEditData('content', e.target.value)}
                                className="min-h-[80px]"
                                required
                            />
                            <InputError message={errors.content} />
                        </div>

                        <div className="flex gap-2">
                            <Button type="submit" size="sm" disabled={editing}>
                                {editing && <LoaderCircle className="animate-spin" />}
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
                            <RichEditorContent content={comment.content} />
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <Button variant="ghost" size="sm" onClick={() => onReply(comment.id)} className="h-auto p-1 text-xs">
                                    <Reply className="mr-1 size-3" />
                                    Reply
                                </Button>
                                {comment.policyPermissions.canUpdate && (
                                    <Button variant="ghost" size="sm" onClick={() => setIsEditing(true)} className="h-auto p-1 text-xs">
                                        <Edit className="mr-1 size-3" />
                                        Edit
                                    </Button>
                                )}
                                {comment.policyPermissions.canDelete && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={handleDelete}
                                        disabled={deleting}
                                        className="h-auto p-1 text-xs text-destructive hover:text-destructive"
                                    >
                                        <Trash className="mr-1 size-3" />
                                        {deleting ? 'Deleting...' : 'Delete'}
                                    </Button>
                                )}
                            </div>
                            <EmojiReactions
                                comment={comment}
                                initialReactions={comment.likesSummary}
                                userReactions={comment.userReactions}
                                className="ml-auto"
                            />
                        </div>
                    </>
                )}

                {replyingTo === comment.id && (
                    <form onSubmit={handleReplySubmit} className="mt-3 space-y-3">
                        <div className="grid gap-2">
                            <Textarea
                                value={data.content}
                                onChange={(e) => setData('content', e.target.value)}
                                placeholder="Write a reply..."
                                className="min-h-[80px]"
                                required
                            />
                            <InputError message={errors.content} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" size="sm" disabled={processing}>
                                {processing && <LoaderCircle className="animate-spin" />}
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

export default function BlogComments({ post, comments }: BlogCommentsProps) {
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
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    const topLevelComments = comments.data.filter((comment) => !comment.parentId) || [];

    if (!post.commentsEnabled) {
        return (
            <div className="space-y-6">
                <div className="flex items-center gap-2">
                    <MessageCircle className="size-5" />
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
                <MessageCircle className="size-5" />
                <HeadingSmall title={`Comments (${comments.data.length || 0})`} />
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid gap-2">
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
                    {processing && <LoaderCircle className="animate-spin" />}
                    {processing ? 'Posting...' : 'Post comment'}
                </Button>
            </form>

            {topLevelComments.length > 0 ? (
                <InfiniteScroll data="comments">
                    <div className="space-y-6">
                        {topLevelComments.map((comment) => (
                            <CommentItem key={comment.id} post={post} comment={comment} onReply={setReplyingTo} replyingTo={replyingTo} />
                        ))}
                    </div>
                </InfiniteScroll>
            ) : (
                <EmptyState title="No comments yet" description="Be the first to share your thoughts!" />
            )}
        </div>
    );
}
