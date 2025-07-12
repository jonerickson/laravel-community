import Product from '@/components/product';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
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

export default function ProductPage() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Product" />
            <div className="px-4 py-6">
                <Product />
            </div>
        </AppLayout>
    );
}
