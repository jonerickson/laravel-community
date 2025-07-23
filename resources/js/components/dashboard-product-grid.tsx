import DashboardProductCard from '@/components/dashboard-product-card';
import HeadingSmall from '@/components/heading-small';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import type { Product } from '@/types';

interface DashboardProductGridProps {
    newestProduct?: Product;
    popularProduct?: Product;
    featuredProduct?: Product;
}

export default function DashboardProductGrid({ newestProduct, popularProduct, featuredProduct }: DashboardProductGridProps) {
    return (
        <div className="grid auto-rows-min gap-4 md:grid-cols-3">
            {/* Newest Arrival Card */}
            <div className="relative">
                {newestProduct ? (
                    <DashboardProductCard product={newestProduct} type="newest" className="h-full" />
                ) : (
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center">
                                <HeadingSmall title="Newest Arrival" description="No products available" />
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Most Popular Card */}
            <div className="relative">
                {popularProduct ? (
                    <DashboardProductCard product={popularProduct} type="popular" className="h-full" />
                ) : (
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center">
                                <HeadingSmall title="Most Popular" description="No products available" />
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Featured Product Card */}
            <div className="relative">
                {featuredProduct ? (
                    <DashboardProductCard product={featuredProduct} type="featured" className="h-full" />
                ) : (
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center">
                                <HeadingSmall title="Featured Product" description="No products available" />
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
