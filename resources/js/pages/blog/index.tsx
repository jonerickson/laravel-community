import BlogIndexItem from '@/components/blog-index-item';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, Post } from '@/types';
import { Head, WhenVisible } from '@inertiajs/react';
import { Newspaper } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Blog',
        href: '/blog',
    },
];

interface BlogIndexProps {
    posts: Post[];
    postsPagination: PaginatedData;
}

export default function BlogIndex({ posts, postsPagination }: BlogIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Blog" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4">
                <div className="sm:flex sm:items-baseline sm:justify-between">
                    <Heading title="Blog" description="Browse our latest blog posts and articles" />
                </div>

                {posts.length > 0 && (
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
                        <EmptyState
                            icon={<Newspaper className="h-12 w-12" />}
                            title="No blog posts"
                            description="Check back later to catch the latest updates"
                        />
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
