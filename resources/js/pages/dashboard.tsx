import DashboardBlogGrid from '@/components/dashboard-blog-grid';
import DashboardProductGrid from '@/components/dashboard-product-grid';
import { EmptyState } from '@/components/empty-state';
import SupportTicketWidget from '@/components/support-ticket-widget';
import TrendingTopicsWidget from '@/components/trending-topics-widget';
import WidgetLoading from '@/components/widget-loading';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Deferred, Head, Link, router } from '@inertiajs/react';
import { Flame, Rss, ShoppingCart, Ticket } from 'lucide-react';
import { route } from 'ziggy-js';

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
                    <div className="space-y-6">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="flex items-center gap-2 text-lg font-semibold">
                                    <ShoppingCart className="size-4 text-destructive" />
                                    Top rated products
                                </h2>
                                <p className="text-sm text-muted-foreground">View the most recent, latest and trending products</p>
                            </div>
                            <Link href={route('store.index')} className="text-sm font-medium text-primary hover:underline">
                                View store
                                <span aria-hidden="true"> &rarr;</span>
                            </Link>
                        </div>

                        <Deferred fallback={<WidgetLoading variant="grid" cols={3} />} data={['newestProduct', 'popularProduct', 'featuredProduct']}>
                            {newestProduct || popularProduct || featuredProduct ? (
                                <DashboardProductGrid
                                    newestProduct={newestProduct}
                                    popularProduct={popularProduct}
                                    featuredProduct={featuredProduct}
                                />
                            ) : (
                                <EmptyState title="No top rated products" description="Check back later for more product options." />
                            )}
                        </Deferred>
                    </div>

                    <div className="space-y-6">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="flex items-center gap-2 text-lg font-semibold">
                                    <Rss className="size-4 text-success" />
                                    Latest blog posts
                                </h2>
                                <p className="text-sm text-muted-foreground">Stay updated with our latest articles and insights</p>
                            </div>
                            <Link href={route('blog.index')} className="text-sm font-medium text-primary hover:underline">
                                View all posts
                                <span aria-hidden="true"> &rarr;</span>
                            </Link>
                        </div>

                        <Deferred fallback={<WidgetLoading variant="grid" cols={4} />} data={'latestBlogPosts'}>
                            {latestBlogPosts && latestBlogPosts.length > 0 ? (
                                <DashboardBlogGrid posts={latestBlogPosts} />
                            ) : (
                                <EmptyState title="No recent blog posts" description="Check back later for our latest articles." />
                            )}
                        </Deferred>
                    </div>

                    <div className="space-y-6">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="flex items-center gap-2 text-lg font-semibold">
                                    <Flame className="size-4 text-orange-400" />
                                    Trending topics
                                </h2>
                                <p className="text-sm text-muted-foreground">The most engaging forum discussions right now</p>
                            </div>
                            <Link href={route('forums.index')} className="text-sm font-medium text-primary hover:underline">
                                View all forums
                                <span aria-hidden="true"> &rarr;</span>
                            </Link>
                        </div>

                        <Deferred fallback={<WidgetLoading />} data={'trendingTopics'}>
                            {trendingTopics && trendingTopics.length > 0 ? (
                                <TrendingTopicsWidget topics={trendingTopics} />
                            ) : (
                                <EmptyState title="No trending topics" description="Check back later for updated content." />
                            )}
                        </Deferred>
                    </div>

                    <div className="space-y-6">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="flex items-center gap-2 text-lg font-semibold">
                                    <Ticket className="size-4 text-info" />
                                    Recent support tickets
                                </h2>
                                <p className="text-sm text-muted-foreground">Your most recent active tickets</p>
                            </div>
                            <Link href={route('support.index')} className="text-sm font-medium text-primary hover:underline">
                                View all tickets
                                <span aria-hidden="true"> &rarr;</span>
                            </Link>
                        </div>

                        <Deferred fallback={<WidgetLoading />} data={'supportTickets'}>
                            {supportTickets && supportTickets.length > 0 ? (
                                <SupportTicketWidget tickets={supportTickets} />
                            ) : (
                                <EmptyState
                                    title="No support tickets"
                                    description="Open a new support ticket to get started."
                                    buttonText="New support ticket"
                                    onButtonClick={() => router.visit(route('support.index'))}
                                />
                            )}
                        </Deferred>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
