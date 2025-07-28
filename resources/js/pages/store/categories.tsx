import StoreCategories from '@/components/store-categories';
import StoreFeatured from '@/components/store-featured';
import StoreUserProvided from '@/components/store-user-provided';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, Product, ProductCategory } from '@/types';
import { Head, WhenVisible } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
];

export default function Categories({
    categories,
    featuredProducts,
    userProvidedProducts,
}: {
    categories: ProductCategory[];
    featuredProducts: Product[];
    userProvidedProducts: Product[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Store" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4">
                {categories.length > 0 && (
                    <WhenVisible fallback={<div className="h-64 animate-pulse rounded-lg bg-muted" />} data={['categories']}>
                        <StoreCategories categories={categories} />
                    </WhenVisible>
                )}

                {featuredProducts.length > 0 && (
                    <WhenVisible fallback={<div className="h-64 animate-pulse rounded-lg bg-muted" />} data={['featuredProducts']}>
                        <StoreFeatured products={featuredProducts} />
                    </WhenVisible>
                )}

                {userProvidedProducts.length > 0 && (
                    <WhenVisible fallback={<div className="h-64 animate-pulse rounded-lg bg-muted" />} data={['userProvidedProducts']}>
                        <StoreUserProvided products={userProvidedProducts} />
                    </WhenVisible>
                )}
            </div>
        </AppLayout>
    );
}
