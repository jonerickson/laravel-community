import BlogIndexItem from '@/components/blog-index-item';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, Post, SharedData } from '@/types';
import { Head, usePage, WhenVisible } from '@inertiajs/react';
import { Newspaper } from 'lucide-react';
import usePermissions from '../../hooks/use-permissions';

interface BlogIndexProps {
    posts: Post[];
    postsPagination: PaginatedData;
}

export default function BlogIndex({ posts, postsPagination }: BlogIndexProps) {
    const { can } = usePermissions();
    const { name: siteName } = usePage<SharedData>().props;

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
        url: window.location.href,
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
        blogPost: posts.map((post) => ({
            '@type': 'BlogPosting',
            headline: post.title,
            description: post.excerpt || post.title,
            author: {
                '@type': 'Person',
                name: post.author?.name,
            },
            datePublished: post.published_at || post.created_at,
            dateModified: post.updated_at,
            image: post.featured_image_url,
            url: route('blog.show', { post: post.slug }),
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
        })),
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Blog">
                <meta name="description" content={`Browse our latest blog posts and articles from ${siteName}`} />
                <meta property="og:title" content={`Blog - ${siteName}`} />
                <meta property="og:description" content={`Browse our latest blog posts and articles from ${siteName}`} />
                <meta property="og:type" content="website" />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                <div className="sm:flex sm:items-baseline sm:justify-between">
                    <Heading title="Blog" description="Browse our latest blog posts and articles" />
                </div>

                {can('view_any_posts') && posts.length > 0 && (
                    <>
                        <div className="mx-auto -my-8 grid max-w-2xl grid-cols-1 gap-8 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                            {posts.map((post) => (
                                <BlogIndexItem key={post.id} post={post} />
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
                                    <HeadingSmall title="There are no more blog posts." description="Check back later." />
                                </div>
                            ) : (
                                <div className="flex items-center justify-center py-8">
                                    <Spinner />
                                </div>
                            )}
                        </WhenVisible>
                    </>
                )}

                {posts.length === 0 && (
                    <div className="-mt-8">
                        <EmptyState icon={<Newspaper />} title="No blog posts" description="Check back later to catch the latest updates" />
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
