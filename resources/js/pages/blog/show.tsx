import BlogComments from '@/components/blog-comments';
import EmojiReactions from '@/components/emoji-reactions';
import HeadingLarge from '@/components/heading-large';
import Loading from '@/components/loading';
import RecentViewers from '@/components/recent-viewers';
import RichEditorContent from '@/components/rich-editor-content';
import { Badge } from '@/components/ui/badge';
import { UserInfo } from '@/components/user-info';
import { useMarkAsRead } from '@/hooks/use-mark-as-read';
import AppLayout from '@/layouts/app-layout';
import { abbreviateNumber, pluralize } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { Deferred, Head, usePage } from '@inertiajs/react';
import { Calendar, Clock, Eye, MessageSquare } from 'lucide-react';
import { route } from 'ziggy-js';

interface BlogShowProps {
    post: App.Data.PostData;
    comments: App.Data.PaginatedData<App.Data.CommentData>;
    recentViewers: App.Data.RecentViewerData[];
}

export default function BlogShow({ post, comments, recentViewers }: BlogShowProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const pageDescription = post.excerpt || post.content.substring(0, 160).replace(/<[^>]*>/g, '') + '...';
    const publishedDate = new Date(post.publishedAt || post.createdAt || new Date());
    const formattedDate = publishedDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Blog',
            href: route('blog.index'),
        },
        {
            title: post.title,
            href: route('blog.show', { post: post.slug }),
        },
    ];

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'BlogPosting',
        headline: post.title,
        description: pageDescription,
        image: post.featuredImageUrl,
        author: {
            '@type': 'Person',
            name: post.author?.name,
        },
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        breadcrumb: {
            '@type': 'BreadcrumbList',
            numberOfItems: breadcrumbs.length,
            itemListElement: breadcrumbs.map((breadcrumb, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                name: breadcrumb.title,
                item: breadcrumb.href,
            })),
        },
        datePublished: post.publishedAt || post.createdAt,
        dateModified: post.updatedAt,
        wordCount: post.content.split(' ').length,
        timeRequired: `PT${post.readingTime}M`,
        interactionStatistic: [
            {
                '@type': 'InteractionCounter',
                interactionType: 'https://schema.org/CommentAction',
                userInteractionCount: post.commentsCount || 0,
            },
            {
                '@type': 'InteractionCounter',
                interactionType: 'https://schema.org/LikeAction',
                userInteractionCount: post.likesCount || 0,
            },
            {
                '@type': 'InteractionCounter',
                interactionType: 'https://schema.org/ViewAction',
                userInteractionCount: post.viewsCount || 0,
            },
        ],
    };

    useMarkAsRead({
        id: post.id,
        type: 'post',
        isRead: post.isReadByUser,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${post.title} - Blog`}>
                <meta name="description" content={pageDescription} />
                <meta property="og:title" content={`${post.title} - Blog - ${siteName}`} />
                <meta property="og:description" content={pageDescription} />
                <meta property="og:type" content="article" />
                <meta property="og:image" content={post.featuredImageUrl || logoUrl} />
                <meta property="article:published_time" content={post.publishedAt || post.createdAt || undefined} />
                <meta property="article:author" content={post.author.name} />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                <article className="mx-auto max-w-4xl" itemScope itemType="https://schema.org/BlogPosting">
                    <header className="mb-8">
                        {post.isFeatured && (
                            <div className="mb-4" role="banner">
                                <Badge aria-label="Featured blog post" variant="secondary">
                                    Featured Post
                                </Badge>
                            </div>
                        )}

                        <HeadingLarge title={post.title} />

                        {post.excerpt && (
                            <p className="-mt-6 mb-6 max-w-3xl text-lg text-muted-foreground" itemProp="description">
                                {post.excerpt}
                            </p>
                        )}

                        <div className="-mt-4 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                            {post.author && (
                                <div itemProp="author" itemScope itemType="https://schema.org/Person">
                                    <UserInfo user={post.author} showGroups={false} />
                                    <meta itemProp="name" content={post.author.name} />
                                </div>
                            )}

                            <div className="flex items-center gap-1">
                                <Calendar className="size-4" aria-hidden="true" />
                                <time
                                    dateTime={post.publishedAt || post.createdAt || undefined}
                                    itemProp="datePublished"
                                    aria-label={`Published on ${formattedDate}`}
                                >
                                    {formattedDate}
                                </time>
                            </div>

                            <div className="flex items-center gap-1">
                                <Eye className="size-4" aria-hidden="true" />
                                <span aria-label={`Total views: ${post.viewsCount} views`}>
                                    {abbreviateNumber(post.viewsCount || 0)} {pluralize('view', post.viewsCount)}
                                </span>
                            </div>

                            {post.commentsEnabled && (
                                <div className="flex items-center gap-1">
                                    <MessageSquare className="size-4" aria-hidden="true" />
                                    <span aria-label={`Total comments: ${post.commentsCount} comments`}>
                                        {abbreviateNumber(post.commentsCount)} {pluralize('comment', post.commentsCount)}
                                    </span>
                                </div>
                            )}

                            {post.readingTime && (
                                <div className="flex items-center gap-1">
                                    <Clock className="size-4" aria-hidden="true" />
                                    <span aria-label={`Estimated reading time: ${post.readingTime} minutes`}>{post.readingTime} min read</span>
                                </div>
                            )}
                        </div>

                        <meta itemProp="dateModified" content={post.updatedAt || undefined} />
                        <meta itemProp="url" content={window.location.href} />
                    </header>

                    <figure className="mb-8" itemProp="image" itemScope itemType="https://schema.org/ImageObject">
                        {post.featuredImageUrl && (
                            <>
                                <img
                                    src={post.featuredImageUrl}
                                    alt={`Featured image for ${post.title}`}
                                    className="relative aspect-video w-full rounded-lg object-cover"
                                    itemProp="url"
                                    loading="eager"
                                />
                                <meta itemProp="width" content="1200" />
                                <meta itemProp="height" content="675" />
                            </>
                        )}
                    </figure>

                    <RichEditorContent itemProp="articleBody" role="main" aria-label="Article content" content={post.content} />

                    <footer className="mt-4">
                        <section aria-label="Post reactions">
                            <EmojiReactions post={post} initialReactions={post.likesSummary} userReactions={post.userReactions} className="mb-4" />
                        </section>

                        <Deferred fallback={<Loading />} data="recentViewers">
                            <div className="mt-6">
                                <RecentViewers viewers={recentViewers} />
                            </div>
                        </Deferred>

                        {post.commentsEnabled && (
                            <section className="mt-8 border-t pt-6" aria-label="Comments section">
                                <BlogComments post={post} comments={comments} />
                            </section>
                        )}
                    </footer>
                </article>
            </div>
        </AppLayout>
    );
}
