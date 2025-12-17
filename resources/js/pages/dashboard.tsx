import BlogIndexItem from '@/components/blog-index-item';
import DashboardProductGrid from '@/components/dashboard-product-grid';
import { EmptyState } from '@/components/empty-state';
import Loading from '@/components/loading';
import SupportTicketWidget from '@/components/support-ticket-widget';
import TrendingTopicsWidget from '@/components/trending-topics-widget';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Deferred, Head, Link, router } from '@inertiajs/react';
import { Flame, Rss, ShoppingCart, Ticket } from 'lucide-react';
import { route } from 'ziggy-js';

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
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: route('dashboard'),
        },
    ];

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
                                Browse store
                                <span aria-hidden="true"> &rarr;</span>
                            </Link>
                        </div>

                        <Deferred fallback={<Loading variant="grid" cols={3} />} data={['newestProduct', 'popularProduct', 'featuredProduct']}>
                            {newestProduct || popularProduct || featuredProduct ? (
                                <DashboardProductGrid
                                    newestProduct={newestProduct}
                                    popularProduct={popularProduct}
                                    featuredProduct={featuredProduct}
                                />
                            ) : (
                                <EmptyState
                                    title="No top rated products"
                                    description="Check back later for more product options."
                                    buttonText="Browse Store"
                                    onButtonClick={() => router.visit(route('store.index'))}
                                />
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
                                View blog
                                <span aria-hidden="true"> &rarr;</span>
                            </Link>
                        </div>

                        <Deferred fallback={<Loading variant="grid" cols={4} />} data={'latestBlogPosts'}>
                            {latestBlogPosts && latestBlogPosts.length > 0 ? (
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                    {latestBlogPosts.map((post) => (
                                        <BlogIndexItem key={post.id} post={post} />
                                    ))}
                                </div>
                            ) : (
                                <EmptyState
                                    title="No recent blog posts"
                                    description="Check back later for our latest articles."
                                    buttonText="View Blog"
                                    onButtonClick={() => router.visit(route('blog.index'))}
                                />
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
                                Explore forums
                                <span aria-hidden="true"> &rarr;</span>
                            </Link>
                        </div>

                        <Deferred fallback={<Loading variant="table" rows={5} cols={3} />} data={'trendingTopics'}>
                            {trendingTopics && trendingTopics.length > 0 ? (
                                <TrendingTopicsWidget topics={trendingTopics} />
                            ) : (
                                <EmptyState
                                    title="No trending topics"
                                    description="Check back later for updated content."
                                    buttonText="Explore Forums"
                                    onButtonClick={() => router.visit(route('forums.index'))}
                                />
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
                                Open support tickets
                                <span aria-hidden="true"> &rarr;</span>
                            </Link>
                        </div>

                        <Deferred fallback={<Loading />} data={'supportTickets'}>
                            {supportTickets && supportTickets.length > 0 ? (
                                <SupportTicketWidget tickets={supportTickets} />
                            ) : (
                                <EmptyState
                                    title="No support tickets"
                                    description="Open a new support ticket to get started."
                                    buttonText="New Support Ticket"
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
