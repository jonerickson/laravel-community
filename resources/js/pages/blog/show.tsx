import BlogPost from '@/components/blog-post';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Comment, PaginatedData, Post } from '@/types';
import { Head } from '@inertiajs/react';

interface BlogShowPageProps {
    post: Post;
    comments: Comment[];
    commentsPagination: PaginatedData;
}

export default function Show({ post, comments, commentsPagination }: BlogShowPageProps) {
    const pageDescription = post.excerpt || post.content.substring(0, 160).replace(/<[^>]*>/g, '') + '...';

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

                <script
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: JSON.stringify({
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
                                name: 'Mountain Interactive',
                            },
                            datePublished: post.published_at || post.created_at,
                            dateModified: post.updated_at,
                            wordCount: post.content.split(' ').length,
                            timeRequired: `PT${post.reading_time}M`,
                        }),
                    }}
                />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4">
                <BlogPost post={post} comments={comments} commentsPagination={commentsPagination} />
            </div>
        </AppLayout>
    );
}
