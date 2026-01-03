import { EmptyState } from '@/components/empty-state';
import { FollowButton } from '@/components/follow-button';
import ForumTopicModerationMenu from '@/components/forum-topic-moderation-menu';
import ForumTopicPost from '@/components/forum-topic-post';
import ForumTopicReply from '@/components/forum-topic-reply';
import Loading from '@/components/loading';
import RecentViewers from '@/components/recent-viewers';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { useMarkAsRead } from '@/hooks/use-mark-as-read';
import AppLayout from '@/layouts/app-layout';
import { abbreviateNumber, pluralize } from '@/lib/utils';
import { buildTopicBreadcrumbs } from '@/utils/breadcrumbs';
import { stripCharacters } from '@/utils/truncate';
import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { AlertTriangle, ArrowDown, ArrowLeft, ArrowUp, Clock, Eye, EyeOff, MessageSquare, Reply, ThumbsDown, User } from 'lucide-react';
import { useMemo, useState } from 'react';
import { route } from 'ziggy-js';

interface TopicShowProps {
    forum: App.Data.ForumData;
    topic: App.Data.TopicData;
    posts: App.Data.PaginatedData<App.Data.PostData>;
    categories: App.Data.ForumCategoryData[];
    recentViewers: App.Data.RecentViewerData[];
}

export default function ForumTopicShow({ forum, topic, posts, categories, recentViewers }: TopicShowProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const [quotedContent, setQuotedContent] = useState<string>('');
    const [quotedAuthor, setQuotedAuthor] = useState<string>('');

    const breadcrumbs = buildTopicBreadcrumbs(forum, topic);

    useMarkAsRead({
        id: topic.id,
        type: 'topic',
        isRead: topic.isReadByUser,
    });

    const goToLatestPost = () => {
        router.reload({
            data: { page: posts.lastPage },
            only: ['posts'],
            onSuccess: () => {
                setTimeout(() => {
                    const postElements = document.querySelectorAll('article[itemtype="https://schema.org/Comment"]');
                    const lastPostElement = postElements[postElements.length - 1];
                    if (lastPostElement) {
                        lastPostElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);
            },
        });
    };

    const goToTheTop = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleQuotePost = (postContent: string, authorName: string) => {
        setQuotedContent(postContent);
        setQuotedAuthor(authorName);

        setTimeout(() => {
            const replyForm = document.querySelector('[data-reply-form]');
            if (replyForm) {
                replyForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    };

    const currentUrl = route('forums.topics.show', { forum: forum.slug, topic: topic.slug });

    const shouldShowBadges = useMemo((): boolean => {
        return (
            topic.isPinned ||
            topic.isLocked ||
            (forum.forumPermissions.canModerate && (topic.hasReportedContent || topic.hasUnpublishedContent || topic.hasUnapprovedContent))
        );
    }, [topic, forum]);

    const handleReplySubmitted = () => {
        router.reload({ only: ['posts'] });
        setQuotedContent('');
        setQuotedAuthor('');
    };

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'DiscussionForumPosting',
        '@id': currentUrl,
        name: topic.title,
        headline: topic.title,
        datePublished: topic.createdAt,
        dateCreated: topic.createdAt,
        dateModified: topic.updatedAt,
        url: currentUrl,
        mainEntityOfPage: currentUrl,
        inLanguage: 'en',
        image: topic.forum?.category?.featuredImageUrl || logoUrl,
        author: {
            '@type': 'Person',
            name: topic.author.name,
            url: topic.author.referenceId ? route('users.show', { user: topic.author.referenceId }) : null,
        },
        publisher: {
            '@type': 'Organization',
            name: siteName,
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
                userInteractionCount: topic.viewsCount || 0,
            },
            {
                '@type': 'InteractionCounter',
                interactionType: 'https://schema.org/CommentAction',
                userInteractionCount: topic.postsCount || 0,
            },
        ],
        commentCount: topic.postsCount || 0,
        comment: posts?.data
            .filter((post) => post.author)
            .map((post) => ({
                '@type': 'Comment',
                '@id': `${currentUrl}#${post.id}`,
                url: `${currentUrl}#${post.id}`,
                text: stripCharacters(post.content),
                datePublished: post.createdAt,
                dateCreated: post.createdAt,
                dateModified: post.updatedAt,
                author: {
                    '@type': 'Person',
                    name: post.author.name,
                    url: post.author.referenceId ? route('users.show', { user: post.author.referenceId }) : null,
                },
                interactionStatistic: [
                    {
                        '@type': 'InteractionCounter',
                        interactionType: 'https://schema.org/LikeAction',
                        userInteractionCount: post.likesCount || 0,
                    },
                ],
            })),
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${topic.title} - ${forum.name} - Forums`}>
                <meta name="description" content={topic.description || `Discussion topic: ${topic.title}`} />
                <meta property="og:title" content={`${topic.title} - ${forum.name} - Forums - ${siteName}`} />
                <meta property="og:description" content={topic.description || `Discussion topic: ${topic.title}`} />
                <meta property="og:type" content="article" />
                <meta property="og:image" content={logoUrl} />
                <meta property="article:author" content={topic.author.name} />
                <meta property="article:published_time" content={topic.createdAt || undefined} />
                <meta property="article:modified_time" content={topic.updatedAt || undefined} />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex flex-col items-start justify-between gap-2 sm:flex-row">
                    <div className="flex w-full items-center justify-between sm:items-start lg:items-center">
                        <div className="mb-2 flex flex-col gap-2 lg:flex-row lg:items-center">
                            {shouldShowBadges && (
                                <div className="flex items-center gap-2">
                                    {forum.forumPermissions.canModerate && (
                                        <>
                                            {topic.hasReportedContent && <AlertTriangle className="size-4 text-destructive" />}
                                            {topic.hasUnpublishedContent && <EyeOff className="size-4 text-warning" />}
                                            {topic.hasUnapprovedContent && <ThumbsDown className="size-4 text-warning" />}
                                        </>
                                    )}
                                    {topic.isPinned && <Badge variant="info">Pinned</Badge>}
                                    {topic.isLocked && <Badge>Locked</Badge>}
                                </div>
                            )}
                            <div className="flex items-center gap-2">
                                {topic.isHot && <span className="text-sm">🔥</span>}
                                <h1 className="text-xl font-semibold tracking-tight">{topic.title}</h1>
                            </div>
                        </div>

                        <Deferred fallback={<></>} data={'categories'}>
                            <ForumTopicModerationMenu topic={topic} forum={forum} categories={categories} />
                        </Deferred>
                    </div>
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:shrink-0 sm:flex-row sm:items-center">
                        <FollowButton
                            type="topic"
                            id={topic.id}
                            isFollowing={topic.isFollowedByUser ?? false}
                            followersCount={topic.followersCount ?? 0}
                            onSuccess={() => router.reload({ only: ['topic'] })}
                        />
                        <Button onClick={goToLatestPost} variant="outline">
                            <ArrowDown />
                            Latest
                        </Button>
                        {forum.forumPermissions.canReply && !topic.isLocked && (
                            <Button
                                onClick={() => document.querySelector('[data-reply-form]')?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
                                variant="outline"
                            >
                                <Reply />
                                Reply
                            </Button>
                        )}
                    </div>
                </div>

                <div className="hidden items-center gap-4 text-sm text-muted-foreground sm:flex md:-mt-6">
                    <div className="flex items-center gap-1">
                        <User className="size-4" />
                        <span>Started by {topic.author.name}</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Eye className="size-4" />
                        <span>
                            {abbreviateNumber(topic.viewsCount)} {pluralize('view', topic.viewsCount)}
                        </span>
                    </div>
                    <div className="flex items-center gap-1">
                        <MessageSquare className="size-4" />
                        <span>
                            {abbreviateNumber(topic.postsCount)} {pluralize('reply', topic.postsCount)}
                        </span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Clock className="size-4" />
                        <span>{topic.createdAt ? formatDistanceToNow(new Date(topic.createdAt), { addSuffix: true }) : 'N/A'}</span>
                    </div>
                </div>

                {posts && posts.data.length > 0 && (
                    <Pagination pagination={posts} baseUrl={route('forums.topics.show', { forum, topic })} entityLabel="post" />
                )}

                <Deferred fallback={<Loading variant="forum-post" count={2} />} data="posts">
                    <div className="mt-0">
                        {posts && posts.data.length > 0 ? (
                            <div className="grid gap-6">
                                {posts.data.map((post, index) => (
                                    <ForumTopicPost key={post.id} post={post} index={index} forum={forum} topic={topic} onQuote={handleQuotePost} />
                                ))}
                            </div>
                        ) : (
                            <EmptyState icon={<MessageSquare />} title="No posts yet" description="This topic doesn't have any posts yet." />
                        )}
                    </div>
                </Deferred>

                {posts && posts.data.length > 0 && (
                    <Pagination pagination={posts} baseUrl={route('forums.topics.show', { forum, topic })} entityLabel="post" />
                )}

                <Deferred fallback={<Loading />} data="recentViewers">
                    <RecentViewers viewers={recentViewers} />
                </Deferred>

                {forum.forumPermissions.canReply && !topic.isLocked && posts && posts.data.length > 0 && (
                    <ForumTopicReply
                        forumSlug={forum.slug}
                        topicSlug={topic.slug}
                        quotedContent={quotedContent}
                        quotedAuthor={quotedAuthor}
                        onSuccess={handleReplySubmitted}
                    />
                )}

                <div className="flex justify-between py-4">
                    <Button variant="ghost" className="text-muted-foreground" asChild>
                        <Link href={route('forums.show', { forum: forum.slug })}>
                            <ArrowLeft className="size-4" />
                            Back to {forum.name}
                        </Link>
                    </Button>
                    <Button variant="ghost" className="text-muted-foreground" onClick={goToTheTop}>
                        <ArrowUp className="size-4" />
                        Back to the top
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
