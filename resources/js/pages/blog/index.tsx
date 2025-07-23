import BlogIndex from '@/components/blog-index';
import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, Post } from '@/types';
import { Head, WhenVisible } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Blog',
        href: '/blog',
    },
];

export default function Index({ posts, postsPagination }: { posts: Post[]; postsPagination: PaginatedData }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Blog" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4">
                <BlogIndex posts={posts} />
                <WhenVisible
                    fallback={<>Loading...</>}
                    always={postsPagination.current_page < postsPagination.last_page}
                    params={{
                        data: {
                            page: postsPagination.current_page + 1,
                        },
                        only: ['posts', 'postsPagination'],
                    }}
                >
                    {postsPagination.current_page >= postsPagination.last_page ? (
                        <div>
                            <HeadingSmall title="There are no more blog posts." description="Check back later." />
                        </div>
                    ) : (
                        <HeadingSmall title="Loading..." />
                    )}
                </WhenVisible>
            </div>
        </AppLayout>
    );
}
