import BlogPost from '@/components/blog-post';
import { useMarkAsRead } from '@/hooks/use-mark-as-read';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Comment, PaginatedData, Post, SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

interface BlogShowProps {
    post: Post;
    comments: Comment[];
    commentsPagination: PaginatedData;
}

export default function BlogShow({ post, comments, commentsPagination }: BlogShowProps) {
    const { name: siteName } = usePage<SharedData>().props;
    const pageDescription = post.excerpt || post.content.substring(0, 160).replace(/<[^>]*>/g, '') + '...';

    useMarkAsRead({
        id: post.id,
        type: 'post',
        isRead: post.is_read_by_user,
    });

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'BlogPosting',
        headline: post.title,
        description: pageDescription,
        image: post.featured_image_url,
        author: {
            '@type': 'Person',
            name: post.author?.name,
        },
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        datePublished: post.published_at || post.created_at,
        dateModified: post.updated_at,
        wordCount: post.content.split(' ').length,
        timeRequired: `PT${post.reading_time}M`,
        interactionStatistic: [
            {
                '@type': 'InteractionCounter',
                interactionType: 'https://schema.org/CommentAction',
                userInteractionCount: post.comments_count || 0,
            },
            {
                '@type': 'InteractionCounter',
                interactionType: 'https://schema.org/LikeAction',
                userInteractionCount: post.likes_count || 0,
            },
            {
                '@type': 'InteractionCounter',
                interactionType: 'https://schema.org/ViewAction',
                userInteractionCount: post.views_count || 0,
            },
        ],
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Blog',
            href: '/blog',
        },
        {
            title: post.title,
            href: `/blog/${post.slug}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head>
                <title>{post.title}</title>
                <meta name="description" content={pageDescription} />
                <meta property="og:title" content={post.title} />
                <meta property="og:description" content={pageDescription} />
                <meta property="og:type" content="article" />
                {post.featured_image_url && <meta property="og:image" content={post.featured_image_url} />}
                <meta property="article:published_time" content={post.published_at || post.created_at} />
                {post.author && <meta property="article:author" content={post.author.name} />}

                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl">
                <BlogPost post={post} comments={comments} commentsPagination={commentsPagination} />
            </div>
        </AppLayout>
    );
}
