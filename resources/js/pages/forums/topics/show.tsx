import { EmptyState } from '@/components/empty-state';
import ForumTopicModerationMenu from '@/components/forum-topic-moderation-menu';
import ForumTopicPost from '@/components/forum-topic-post';
import ForumTopicReply from '@/components/forum-topic-reply';
import RecentViewers from '@/components/recent-viewers';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { useMarkAsRead } from '@/hooks/use-mark-as-read';
import AppLayout from '@/layouts/app-layout';
import { pluralize } from '@/lib/utils';
import type { BreadcrumbItem, Forum, PaginatedData, Post, Topic } from '@/types';
import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { ArrowDown, ArrowLeft, Clock, Eye, Lock, MessageSquare, Pin, Reply, User } from 'lucide-react';
import { useEffect, useState } from 'react';
import { route } from 'ziggy-js';
import usePermissions from '../../../hooks/use-permissions';

interface RecentViewer {
    user: {
        id: number;
        name: string;
        avatar?: string;
    };
    viewed_at: string;
}

interface TopicShowProps {
    forum: Forum;
    topic: Topic;
    posts: Post[];
    postsPagination: PaginatedData;
    recentViewers: RecentViewer[];
}

export default function ForumTopicShow({ forum, topic, posts, postsPagination, recentViewers }: TopicShowProps) {
    const { can, cannot } = usePermissions();
    const { name: siteName } = usePage<App.Data.SharedData>().props;
    const [showReplyForm, setShowReplyForm] = useState(false);
    const [quotedContent, setQuotedContent] = useState<string>('');
    const [quotedAuthor, setQuotedAuthor] = useState<string>('');
    const scrollToBottom = usePage<App.Data.SharedData>().props.flash?.scrollToBottom;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Forums',
            href: route('forums.index'),
        },
        {
            title: forum.category.name,
            href: route('forums.categories.show', { category: forum.category.slug }),
        },
        {
            title: forum.name,
            href: route('forums.show', { forum: forum.slug }),
        },
        {
            title: topic.title,
            href: route('forums.topics.show', { forum: forum.slug, topic: topic.slug }),
        },
    ];

    useMarkAsRead({
        id: topic.id,
        type: 'topic',
        isRead: topic.is_read_by_user,
    });

    useEffect(() => {
        if (scrollToBottom) {
            setTimeout(() => {
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            }, 100);
        }
    }, [scrollToBottom]);

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

    const handleQuotePost = (postContent: string, authorName: string) => {
        if (cannot('reply_topics')) {
            return;
        }

        setQuotedContent(postContent);
        setQuotedAuthor(authorName);
        setShowReplyForm(true);

        setTimeout(() => {
            const replyForm = document.querySelector('[data-reply-form]');
            if (replyForm) {
                replyForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    };

    const handleReplySuccess = () => {
        setShowReplyForm(false);
        setQuotedContent('');
        setQuotedAuthor('');
    };

    const handleReplyCancel = () => {
        setShowReplyForm(false);
        setQuotedContent('');
        setQuotedAuthor('');
    };

    const currentUrl = route('forums.topics.show', { forum: forum.slug, topic: topic.slug });

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'DiscussionForumPosting',
        '@id': currentUrl,
        headline: topic.title,
        description: topic.description || `Discussion topic: ${topic.title}`,
        dateCreated: topic.created_at,
        dateModified: topic.updated_at,
        url: currentUrl,
        mainEntityOfPage: {
            '@type': 'WebPage',
            '@id': currentUrl,
        },
        author: {
            '@type': 'Person',
            name: topic.author.name,
        },
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        isPartOf: {
            '@type': 'CollectionPage',
            name: forum.name,
            url: route('forums.show', { forum: forum.slug }),
        },
        breadcrumb: {
            '@type': 'BreadcrumbList',
            itemListElement: breadcrumbs.map((breadcrumb, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                name: breadcrumb.title,
                '@id': breadcrumb.href,
            })),
        },
        interactionStatistic: [
            {
                '@type': 'InteractionCounter',
                interactionType: 'https://schema.org/ViewAction',
                userInteractionCount: topic.views_count || 0,
            },
            {
                '@type': 'InteractionCounter',
                interactionType: 'https://schema.org/ReplyAction',
                userInteractionCount: topic.posts_count || 0,
            },
        ],
        commentCount: topic.posts_count || 0,
        comment: posts
            .filter((post) => post.author)
            .map((post) => ({
                '@type': 'Comment',
                '@id': `${currentUrl}#post-${post.id}`,
                text: post.content,
                dateCreated: post.created_at,
                dateModified: post.updated_at,
                author: {
                    '@type': 'Person',
                    name: post.author.name,
                },
            })),
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Forums - ${forum.name} - ${topic.title}`}>
                <meta name="description" content={topic.description || `Discussion topic: ${topic.title}`} />
                <meta property="og:title" content={`${topic.title} - ${forum.name} - Forums`} />
                <meta property="og:description" content={topic.description || `Discussion topic: ${topic.title}`} />
                <meta property="og:type" content="article" />
                <meta property="article:author" content={topic.author.name} />
                <meta property="article:published_time" content={topic.created_at} />
                <meta property="article:modified_time" content={topic.updated_at} />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                <div className="flex flex-col items-start justify-between gap-2 sm:flex-row">
                    <div className="mb-4 flex w-full items-start justify-between gap-2">
                        <div className="flex-1">
                            <div className="mb-2 flex items-center gap-2">
                                {topic.is_hot && <span className="text-sm">🔥</span>}
                                {topic.is_pinned && <Pin className="size-4 text-info" />}
                                {topic.is_locked && <Lock className="size-4 text-muted-foreground" />}
                                <h1 className="text-xl font-semibold tracking-tight">{topic.title}</h1>
                            </div>

                            {topic.description && <p className="max-w-3xl text-sm text-muted-foreground">{topic.description}</p>}
                        </div>

                        <ForumTopicModerationMenu topic={topic} forum={forum} />
                    </div>
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:shrink-0 sm:flex-row sm:items-center">
                        <Button onClick={goToLatestPost} variant="outline">
                            <ArrowDown className="mr-2 size-4" />
                            Latest
                        </Button>
                        {can('reply_topics') && !topic.is_locked && (
                            <Button onClick={() => setShowReplyForm(!showReplyForm)} variant={showReplyForm ? 'outline' : 'default'}>
                                <Reply className="mr-2 size-4" />
                                Reply
                            </Button>
                        )}
                    </div>
                </div>

                <div className="hidden items-center gap-4 text-sm text-muted-foreground sm:flex md:-mt-4">
                    <div className="flex items-center gap-1">
                        <User className="size-4" />
                        <span>Started by {topic.author.name}</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Eye className="size-4" />
                        <span>
                            {topic.views_count} {pluralize('view', topic.views_count)}
                        </span>
                    </div>
                    <div className="flex items-center gap-1">
                        <MessageSquare className="size-4" />
                        <span>
                            {topic.posts_count} {pluralize('reply', topic.posts_count)}
                        </span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Clock className="size-4" />
                        <span>{formatDistanceToNow(new Date(topic.created_at), { addSuffix: true })}</span>
                    </div>
                </div>

                {showReplyForm && can('reply_topics') && (
                    <ForumTopicReply
                        forumSlug={forum.slug}
                        topicSlug={topic.slug}
                        onCancel={handleReplyCancel}
                        onSuccess={handleReplySuccess}
                        quotedContent={quotedContent}
                        quotedAuthor={quotedAuthor}
                    />
                )}

                <Pagination
                    pagination={postsPagination}
                    baseUrl={route('forums.topics.show', { forum: forum.slug, topic: topic.slug })}
                    entityLabel="post"
                    className="hidden md:flex"
                />

                {posts.length > 0 ? (
                    <div className="grid gap-4">
                        {posts.map((post, index) => (
                            <ForumTopicPost key={post.id} post={post} index={index} forum={forum} topic={topic} onQuote={handleQuotePost} />
                        ))}
                    </div>
                ) : (
                    <EmptyState icon={<MessageSquare />} title="No posts yet" description="This topic doesn't have any posts yet." />
                )}

                <Deferred fallback={null} data="recentViewers">
                    <RecentViewers viewers={recentViewers} />
                </Deferred>

                {!topic.is_locked && posts.length > 0 && can('reply_topics') && <ForumTopicReply forumSlug={forum.slug} topicSlug={topic.slug} />}

                <Pagination
                    pagination={postsPagination}
                    baseUrl={route('forums.topics.show', { forum: forum.slug, topic: topic.slug })}
                    entityLabel="post"
                    className="pb-4"
                />

                <div className="flex justify-start py-4">
                    <Link
                        href={route('forums.show', { forum: forum.slug })}
                        className="flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Back to {forum.name}
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
