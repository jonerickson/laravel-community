import BlogPost from '@/components/blog-post';
import { useMarkAsRead } from '@/hooks/use-mark-as-read';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';

interface BlogShowProps {
    post: App.Data.PostData;
    comments: App.Data.CommentData[];
    commentsPagination: App.Data.PaginatedData;
    recentViewers: App.Data.RecentViewerData[];
}

export default function BlogShow({ post, comments, commentsPagination, recentViewers }: BlogShowProps) {
    const { name: siteName } = usePage<App.Data.SharedData>().props;
    const pageDescription = post.excerpt || post.content.substring(0, 160).replace(/<[^>]*>/g, '') + '...';

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
            <Head>
                <title>{post.title}</title>
                <meta name="description" content={pageDescription} />
                <meta property="og:title" content={post.title} />
                <meta property="og:description" content={pageDescription} />
                <meta property="og:type" content="article" />
                {post.featuredImageUrl && <meta property="og:image" content={post.featuredImageUrl} />}
                <meta property="article:published_time" content={post.publishedAt || post.createdAt || undefined} />
                {post.author && <meta property="article:author" content={post.author.name} />}
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                <BlogPost post={post} comments={comments} commentsPagination={commentsPagination} recentViewers={recentViewers} />
            </div>
        </AppLayout>
    );
}
