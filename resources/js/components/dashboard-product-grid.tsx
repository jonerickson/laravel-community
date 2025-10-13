import DashboardProductCard from '@/components/dashboard-product-card';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { Link } from '@inertiajs/react';
import { Store } from 'lucide-react';

interface DashboardProductGridProps {
    newestProduct?: App.Data.ProductData;
    popularProduct?: App.Data.ProductData;
    featuredProduct?: App.Data.ProductData;
}

export default function DashboardProductGrid({ newestProduct, popularProduct, featuredProduct }: DashboardProductGridProps) {
    if (!newestProduct && !popularProduct && !featuredProduct) {
        return null;
    }

    return (
        <div className="grid auto-rows-min gap-6 md:grid-cols-3">
            <div className="relative">
                {newestProduct ? (
                    <DashboardProductCard product={newestProduct} type="newest" />
                ) : (
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="space-y-3 text-center">
                                <HeadingSmall title="Newest arrival" description="No products available" />
                                <Button asChild variant="outline" size="sm">
                                    <Link href={route('store.index')}>
                                        <Store className="size-4" />
                                        Shop store
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            <div className="relative">
                {popularProduct ? (
                    <DashboardProductCard product={popularProduct} type="popular" />
                ) : (
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="space-y-3 text-center">
                                <HeadingSmall title="Most popular" description="No products available" />
                                <Button asChild variant="outline" size="sm">
                                    <Link href={route('store.index')}>
                                        <Store className="size-4" />
                                        Shop store
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            <div className="relative">
                {featuredProduct ? (
                    <DashboardProductCard product={featuredProduct} type="featured" />
                ) : (
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="space-y-3 text-center">
                                <HeadingSmall title="Featured product" description="No products available" />
                                <Button asChild variant="outline" size="sm">
                                    <Link href={route('store.index')}>
                                        <Store className="size-4" />
                                        Shop store
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
