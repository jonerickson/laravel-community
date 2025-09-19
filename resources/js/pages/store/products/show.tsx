import Product from '@/components/store-product';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData } from '@/types';
import { Head } from '@inertiajs/react';

interface ProductPageProps {
    product: App.Data.ProductData;
    reviews: App.Data.CommentData[];
    reviewsPagination: PaginatedData;
}

export default function StoreProductShow({ product, reviews, reviewsPagination }: ProductPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Store',
            href: route('store.index'),
        },
        {
            title: product.name,
            href: route('store.products.show', { product: product.slug }),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={product.name} />
            <div className="py-2">
                <Product product={product} reviews={reviews} reviewsPagination={reviewsPagination} />
            </div>
        </AppLayout>
    );
}
