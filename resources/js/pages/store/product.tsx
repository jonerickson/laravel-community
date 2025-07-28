import Product from '@/components/product';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Product as ProductType } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
    {
        title: 'Product',
        href: '/store/product',
    },
];

interface ProductPageProps {
    product: ProductType;
}

export default function ProductPage({ product }: ProductPageProps) {
    const productBreadcrumbs: BreadcrumbItem[] = [
        ...breadcrumbs,
        {
            title: product.name,
            href: `/store/products/${product.slug}`,
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
