import Product from '@/components/store-product';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Comment, PaginatedData, Product as ProductType } from '@/types';
import { Head } from '@inertiajs/react';

interface ProductPageProps {
    product: ProductType;
    reviews: Comment[];
    reviewsPagination: PaginatedData;
}

export default function StoreProductShow({ product, reviews, reviewsPagination }: ProductPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Store',
            href: '/store',
        },
        {
            title: product.name,
            href: `/store/products/${product.slug}`,
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
