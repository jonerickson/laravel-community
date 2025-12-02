import BlogIndexItem from '@/components/blog-index-item';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, InfiniteScroll, usePage } from '@inertiajs/react';
import { Newspaper } from 'lucide-react';
import { route } from 'ziggy-js';

interface BlogIndexProps {
    posts: App.Data.PaginatedData<App.Data.PostData>;
}

export default function BlogIndex({ posts }: BlogIndexProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Blog',
            href: route('blog.index'),
        },
    ];

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'Blog',
        name: `${siteName} Blog`,
        description: 'Browse our latest blog posts and articles',
        url: route('blog.index'),
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
        blogPost: posts.data.map((post) => ({
            '@type': 'BlogPosting',
            headline: post.title,
            description: post.excerpt || post.title,
            author: {
                '@type': 'Person',
                name: post.author?.name,
            },
            datePublished: post.publishedAt || post.createdAt,
            dateModified: post.updatedAt,
            image: post.featuredImageUrl,
            url: route('blog.show', { post: post.slug }),
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
        })),
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Blog">
                <meta name="description" content={`Browse our latest blog posts and articles from ${siteName}`} />
                <meta property="og:title" content={`Blog - ${siteName}`} />
                <meta property="og:description" content={`Browse our latest blog posts and articles from ${siteName}`} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                <Heading title="Blog" description="Browse our latest blog posts and articles" />

                <div className="-mt-8">
                    {posts.data.length > 0 ? (
                        <InfiniteScroll data="posts" loading={() => 'Loading more users...'}>
                            <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                                {posts.data.map((post) => (
                                    <BlogIndexItem key={post.id} post={post} />
                                ))}
                            </div>
                        </InfiniteScroll>
                    ) : (
                        <EmptyState icon={<Newspaper />} title="No blog posts" description="Check back later to catch the latest updates." />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
