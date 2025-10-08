import BlogPostsGrid from '@/components/blog-posts-grid';
import DashboardProductGrid from '@/components/dashboard-product-grid';
import SupportTicketWidget from '@/components/support-ticket-widget';
import TrendingTopicsWidget from '@/components/trending-topics-widget';
import WidgetLoading from '@/components/widget-loading';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Deferred, Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

interface DashboardProps {
    newestProduct?: App.Data.ProductData;
    popularProduct?: App.Data.ProductData;
    featuredProduct?: App.Data.ProductData;
    supportTickets?: App.Data.SupportTicketData[];
    trendingTopics?: App.Data.TopicData[];
    latestBlogPosts?: App.Data.PostData[];
}

export default function Dashboard({
    newestProduct,
    popularProduct,
    featuredProduct,
    supportTickets = [],
    trendingTopics = [],
    latestBlogPosts = [],
}: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="relative flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                <div className="relative z-10 flex flex-col gap-6">
                    <DashboardProductGrid newestProduct={newestProduct} popularProduct={popularProduct} featuredProduct={featuredProduct} />

                    <Deferred fallback={<WidgetLoading />} data={'latestBlogPosts'}>
                        <BlogPostsGrid posts={latestBlogPosts} />
                    </Deferred>

                    <Deferred fallback={<WidgetLoading />} data={'trendingTopics'}>
                        <TrendingTopicsWidget topics={trendingTopics} />
                    </Deferred>

                    <Deferred fallback={<WidgetLoading />} data={'supportTickets'}>
                        <SupportTicketWidget tickets={supportTickets} />
                    </Deferred>
                </div>
            </div>
        </AppLayout>
    );
}
