import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import StoreIndexCategories from '@/components/store-index-categories';
import StoreIndexFeatured from '@/components/store-index-featured';
import StoreIndexUserProvided from '@/components/store-index-user-provided';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, WhenVisible } from '@inertiajs/react';
import { ShoppingCart } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: route('store.index'),
    },
];

interface StoreIndexProps {
    categories: App.Data.ProductCategoryData[];
    featuredProducts: App.Data.ProductData[];
    userProvidedProducts: App.Data.ProductData[];
}

export default function StoreIndex({ categories, featuredProducts, userProvidedProducts }: StoreIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Store" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                {categories.length > 0 && (
                    <WhenVisible fallback={<div className="h-64 animate-pulse rounded-lg bg-muted" />} data={['categories']}>
                        <StoreIndexCategories categories={categories} />
                    </WhenVisible>
                )}

                {featuredProducts.length > 0 && (
                    <WhenVisible fallback={<div className="h-64 animate-pulse rounded-lg bg-muted" />} data={['featuredProducts']}>
                        <StoreIndexFeatured products={featuredProducts} />
                    </WhenVisible>
                )}

                {userProvidedProducts.length > 0 && (
                    <WhenVisible fallback={<div className="h-64 animate-pulse rounded-lg bg-muted" />} data={['userProvidedProducts']}>
                        <StoreIndexUserProvided products={userProvidedProducts} />
                    </WhenVisible>
                )}

                {categories.length === 0 && featuredProducts.length === 0 && userProvidedProducts.length === 0 && (
                    <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                        <div className="sm:flex sm:items-baseline sm:justify-between">
                            <Heading title="Store" description="No products available" />
                        </div>

                        <div className="-mt-8">
                            <EmptyState icon={<ShoppingCart />} title="No products available" description="Check back later for new categories." />
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
