import Product from '@/components/store-product';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, ProductCategory, Product as ProductType } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
];

interface ProductPageProps {
    product: ProductType;
    category?: ProductCategory;
}

export default function ProductPage({ product, category }: ProductPageProps) {
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
            <div className="px-4 py-6">
                <Product product={product} />
            </div>
        </AppLayout>
    );
}
