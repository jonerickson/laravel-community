import StoreIndexCategories from '@/components/store-index-categories';
import StoreIndexFeatured from '@/components/store-index-featured';
import StoreIndexUserProvided from '@/components/store-index-user-provided';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, Product, ProductCategory } from '@/types';
import { Head, WhenVisible } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
];

interface StoreIndexProps {
    categories: ProductCategory[];
    featuredProducts: Product[];
    userProvidedProducts: Product[];
}

export default function StoreIndex({ categories, featuredProducts, userProvidedProducts }: StoreIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Store" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl">
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
            </div>
        </AppLayout>
    );
}
