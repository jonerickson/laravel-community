import BlogIndex from '@/components/blog-index';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Post } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Blog',
        href: '/blog',
    },
];

export default function Index({ posts }: { posts: Post[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Blog" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4">
                <BlogIndex posts={posts} />
            </div>
        </AppLayout>
    );
}
