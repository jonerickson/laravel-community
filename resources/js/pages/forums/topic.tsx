import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Forum, PaginatedData, Post, Topic } from '@/types';
import { Head, useForm, WhenVisible } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Clock, Eye, Lock, MessageSquare, Pin, Reply, User } from 'lucide-react';
import { useState } from 'react';

interface TopicShowProps {
    forum: Forum;
    topic: Topic;
    posts: Post[];
    postsPagination: PaginatedData;
}

export default function TopicShow({ forum, topic, posts, postsPagination }: TopicShowProps) {
    const [showReplyForm, setShowReplyForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        content: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Forums',
            href: '/forums',
        },
        {
            title: forum.name,
            href: `/forums/${forum.slug}`,
        },
        {
            title: topic.title,
            href: `/forums/${forum.slug}/${topic.slug}`,
        },
    ];

    const handleReply = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/forums/${forum.slug}/${topic.slug}/reply`, {
            onSuccess: () => {
                reset();
                setShowReplyForm(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${topic.title} - ${forum.name} - Forums`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <div className="mb-2 flex items-center gap-2">
                            {topic.is_pinned && <Pin className="h-5 w-5 text-blue-500" />}
                            {topic.is_locked && <Lock className="h-5 w-5 text-gray-500" />}
                            <h1 className="text-2xl font-bold">{topic.title}</h1>
                        </div>

                        {topic.description && <p className="mb-4 text-muted-foreground">{topic.description}</p>}

                        <div className="flex items-center gap-4 text-sm text-muted-foreground">
                            <div className="flex items-center gap-1">
                                <User className="h-4 w-4" />
                                <span>Started by {topic.author?.name}</span>
                            </div>
                            <div className="flex items-center gap-1">
                                <Eye className="h-4 w-4" />
                                <span>{topic.views_count} views</span>
                            </div>
                            <div className="flex items-center gap-1">
                                <MessageSquare className="h-4 w-4" />
                                <span>{topic.posts_count} replies</span>
                            </div>
                            <div className="flex items-center gap-1">
                                <Clock className="h-4 w-4" />
                                <span>{formatDistanceToNow(new Date(topic.created_at), { addSuffix: true })}</span>
                            </div>
                        </div>
                    </div>

                    {!topic.is_locked && (
                        <Button onClick={() => setShowReplyForm(!showReplyForm)} variant={showReplyForm ? 'outline' : 'default'}>
                            <Reply className="mr-2 h-4 w-4" />
                            Reply
                        </Button>
                    )}
                </div>

                <div className="grid gap-4">
                    {posts.map((post, index) => (
                        <Card key={post.id}>
                            <CardContent className="p-6">
                                <div className="flex gap-4">
                                    <div className="flex min-w-0 flex-col items-center gap-2">
                                        <Avatar className="h-12 w-12">
                                            <AvatarFallback>{post.author?.name.charAt(0).toUpperCase()}</AvatarFallback>
                                        </Avatar>
                                        <div className="text-center">
                                            <div className="text-sm font-medium">{post.author?.name}</div>
                                            <div className="text-xs text-muted-foreground">{index === 0 ? 'Author' : 'Member'}</div>
                                        </div>
                                    </div>

                                    <div className="min-w-0 flex-1">
                                        <div className="mb-4 flex items-center justify-between">
                                            <div className="text-sm text-muted-foreground">
                                                Posted {formatDistanceToNow(new Date(post.created_at), { addSuffix: true })}
                                            </div>
                                        </div>

                                        <div className="prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: post.content }} />

                                        {post.comments && post.comments.length > 0 && (
                                            <div className="mt-6 border-t pt-4">
                                                <div className="mb-3 text-sm font-medium">Comments</div>
                                                <div className="space-y-3">
                                                    {post.comments.map((comment) => (
                                                        <div key={comment.id} className="flex gap-3 rounded-lg bg-muted/50 p-3">
                                                            <Avatar className="h-8 w-8">
                                                                <AvatarFallback className="text-xs">
                                                                    {comment.user?.name.charAt(0).toUpperCase()}
                                                                </AvatarFallback>
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
                    ))}
                </div>

                <WhenVisible
                    fallback={<></>}
                    always={postsPagination.current_page < postsPagination.last_page}
                    params={{
                        data: {
                            page: postsPagination.current_page + 1,
                        },
                        only: ['posts', 'postsPagination'],
                    }}
                >
                    {postsPagination.current_page >= postsPagination.last_page ? (
                        <div className="flex items-center justify-center py-8 text-center">
                            <HeadingSmall title="There are no more posts." description="Check back later." />
                        </div>
                    ) : (
                        <div className="flex items-center justify-center py-8">
                            <Spinner />
                        </div>
                    )}
                </WhenVisible>

                {showReplyForm && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Reply to Topic</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleReply} className="space-y-4">
                                <div>
                                    <Textarea
                                        placeholder="Write your reply..."
                                        value={data.content}
                                        onChange={(e) => setData('content', e.target.value)}
                                        rows={6}
                                        required
                                    />
                                    {errors.content && <div className="mt-1 text-sm text-red-600">{errors.content}</div>}
                                </div>

                                <div className="flex items-center gap-2">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Posting...' : 'Post Reply'}
                                    </Button>
                                    <Button type="button" variant="outline" onClick={() => setShowReplyForm(false)}>
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {posts.length === 0 && (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <MessageSquare className="mb-4 h-12 w-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No Posts Yet</CardTitle>
                            <CardDescription>This topic doesn't have any posts yet.</CardDescription>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
