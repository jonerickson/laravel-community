import AnnouncementsList from '@/components/announcements-list';
import BlogPostsGrid from '@/components/blog-posts-grid';
import DashboardProductGrid from '@/components/dashboard-product-grid';
import SupportTicketWidget from '@/components/support-ticket-widget';
import TrendingTopicsWidget from '@/components/trending-topics-widget';
import WidgetLoading from '@/components/widget-loading';
import { useMarkAsRead } from '@/hooks/use-mark-as-read';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Post, type Product, type SupportTicket, type Topic } from '@/types';
import { Deferred, Head } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

interface DashboardProps {
    newestProduct?: Product;
    popularProduct?: Product;
    featuredProduct?: Product;
    announcements?: App.Data.AnnouncementData[];
    supportTickets?: SupportTicket[];
    trendingTopics?: Topic[];
    latestBlogPosts?: Post[];
}

export default function Dashboard({
    newestProduct,
    popularProduct,
    featuredProduct,
    announcements = [],
    supportTickets = [],
    trendingTopics = [],
    latestBlogPosts = [],
}: DashboardProps) {
    const [dismissedAnnouncementId, setDismissedAnnouncementId] = useState<number | null>(null);

    useMarkAsRead({
        id: dismissedAnnouncementId || 0,
        type: 'announcement',
        isRead: false,
        enabled: dismissedAnnouncementId !== null,
    });

    const handleAnnouncementDismiss = (announcementId: number) => {
        setDismissedAnnouncementId(announcementId);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="relative flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                <div className="relative z-10 flex flex-col gap-4">
                    {announcements.length > 0 && (
                        <Deferred fallback={<WidgetLoading />} data={'announcements'}>
                            <AnnouncementsList announcements={announcements} onDismiss={handleAnnouncementDismiss} />
                        </Deferred>
                    )}

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
