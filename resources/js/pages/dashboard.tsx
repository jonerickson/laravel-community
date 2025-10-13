import DashboardBlogGrid from '@/components/dashboard-blog-grid';
import DashboardProductGrid from '@/components/dashboard-product-grid';
import SupportTicketWidget from '@/components/support-ticket-widget';
import TrendingTopicsWidget from '@/components/trending-topics-widget';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty';
import WidgetLoading from '@/components/widget-loading';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Deferred, Head, Link } from '@inertiajs/react';
import { Flame, PlusIcon, Rss, ShoppingCart, Ticket } from 'lucide-react';

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
                                <Empty className="border border-dashed">
                                    <EmptyHeader>
                                        <EmptyTitle>No top rated products</EmptyTitle>
                                        <EmptyDescription>Check back later for more product options.</EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
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
                                <Empty className="border border-dashed">
                                    <EmptyHeader>
                                        <EmptyTitle>No recent blog posts</EmptyTitle>
                                        <EmptyDescription>Check back later for our latest articles.</EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
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
                                <Empty className="border border-dashed">
                                    <EmptyHeader>
                                        <EmptyTitle>No trending topics</EmptyTitle>
                                        <EmptyDescription>Check back later for updated content.</EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
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
                                <Empty className="border border-dashed">
                                    <EmptyHeader>
                                        <EmptyTitle>No support tickets</EmptyTitle>
                                        <EmptyDescription>Open a new support ticket to get started.</EmptyDescription>
                                    </EmptyHeader>
                                    <EmptyContent>
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={route('support.create')}>
                                                <PlusIcon />
                                                New support ticket
                                            </Link>
                                        </Button>
                                    </EmptyContent>
                                </Empty>
                            )}
                        </Deferred>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
