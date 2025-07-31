import { EmptyState } from '@/components/empty-state';
import ForumTopicPost from '@/components/forum-topic-post';
import ForumTopicReply from '@/components/forum-topic-reply';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Forum, PaginatedData, Post, Topic } from '@/types';
import { ApiError, apiRequest } from '@/utils/api';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { formatDistanceToNow } from 'date-fns';
import { ArrowDown, ArrowLeft, Clock, Eye, Lock, MessageSquare, Pin, Reply, User } from 'lucide-react';
import { useEffect, useState } from 'react';

interface TopicShowProps {
    forum: Forum;
    topic: Topic;
    posts: Post[];
    postsPagination: PaginatedData;
}

export default function TopicShow({ forum, topic, posts, postsPagination }: TopicShowProps) {
    const [showReplyForm, setShowReplyForm] = useState(false);

    useEffect(() => {
        if (!topic.is_read_by_user) {
            const markAsRead = async () => {
                try {
                    await apiRequest(
                        axios.post(route('api.read'), {
                            type: 'topic',
                            id: topic.id,
                        }),
                    );
                } catch (error) {
                    console.error('Error marking topic as read:', error);
                    const apiError = error as ApiError;
                    console.error('API Error:', apiError.message);
                }
            };

            markAsRead();
        }
    }, [topic.id, topic.is_read_by_user]);

    const topicUrl = `/forums/${forum.slug}/${topic.slug}`;

    const goToLatestPost = () => {
        router.reload({
            data: { page: postsPagination.last_page },
            only: ['posts', 'postsPagination'],
            onSuccess: () => {
                setTimeout(() => {
                    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
                }, 100);
            },
        });
    };

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${topic.title} - ${forum.name} - Forums`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
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
                        <div className="flex gap-2">
                            <Button onClick={goToLatestPost} variant="outline">
                                <ArrowDown className="mr-2 h-4 w-4" />
                                Latest
                            </Button>
                            <Button onClick={() => setShowReplyForm(!showReplyForm)} variant={showReplyForm ? 'outline' : 'default'}>
                                <Reply className="mr-2 h-4 w-4" />
                                Reply
                            </Button>
                        </div>
                    )}
                </div>

                {showReplyForm && (
                    <div className="pt-4">
                        <ForumTopicReply
                            forumSlug={forum.slug}
                            topicSlug={topic.slug}
                            onCancel={() => setShowReplyForm(false)}
                            onSuccess={() => setShowReplyForm(false)}
                        />
                    </div>
                )}

                <Pagination pagination={postsPagination} baseUrl={topicUrl} entityLabel="post" className="py-4" />

                {posts.length > 0 && (
                    <div className="grid gap-4">
                        {posts.map((post, index) => (
                            <ForumTopicPost key={post.id} post={post} index={index} />
                        ))}
                    </div>
                )}

                {posts.length === 0 && (
                    <div className="mt-2">
                        <EmptyState
                            icon={<MessageSquare className="h-12 w-12" />}
                            title="No posts yet"
                            description="This topic doesn't have any posts yet."
                        />
                    </div>
                )}

                {!topic.is_locked && posts.length > 0 && (
                    <div className="pt-4">
                        <ForumTopicReply
                            forumSlug={forum.slug}
                            topicSlug={topic.slug}
                            onCancel={() => setShowReplyForm(false)}
                            onSuccess={() => setShowReplyForm(false)}
                        />
                    </div>
                )}

                <Pagination pagination={postsPagination} baseUrl={topicUrl} entityLabel="post" className="py-4" />

                <div className="flex justify-start py-4">
                    <Link
                        href={`/forums/${forum.slug}`}
                        className="flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to {forum.name}
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
