import Product from '@/components/store-product';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Comment, PaginatedData, ProductCategory, Product as ProductType } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
];

interface ProductPageProps {
    product: ProductType;
    reviews: Comment[];
    reviewsPagination: PaginatedData;
    category?: ProductCategory;
}

export default function ProductPage({ product, reviews, reviewsPagination, category }: ProductPageProps) {
    const productBreadcrumbs: BreadcrumbItem[] = [
        ...breadcrumbs,
        ...(category
            ? [
                  {
                      title: category.name,
                      href: `/store/categories/${category.slug}`,
                  },
              ]
            : []),
        {
            title: product.name,
            href: category ? `/store/categories/${category.slug}/products/${product.slug}` : `/store/products/${product.slug}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={productBreadcrumbs}>
            <Head title={product.name} />
            <div className="py-2">
                <Product product={product} reviews={reviews} reviewsPagination={reviewsPagination} />
            </div>
        </AppLayout>
    );
}
