import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import StoreCategoriesProductItem from '@/components/store-categories-product-item';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Product, ProductCategory } from '@/types';
import { Head } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
];

interface StoreCategoryShowProps {
    category: ProductCategory;
    products: Product[];
}

export default function StoreCategoryShow({ category, products }: StoreCategoryShowProps) {
    const categoryBreadcrumbs: BreadcrumbItem[] = [
        ...breadcrumbs,
        {
            title: category.name,
            href: `/store/categories/${category.slug}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={categoryBreadcrumbs}>
            <Head title={`${category.name} - Store`} />
            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <Heading title={category.name} description={category.description} />

                {products.length > 0 ? (
                    <div className="-my-6 grid grid-cols-2 sm:-mx-6 md:grid-cols-3 lg:grid-cols-4">
                        {products.map((product) => (
                            <StoreCategoriesProductItem product={product} />
                        ))}
                    </div>
                ) : (
                    <div className="mt-8">
                        <EmptyState
                            icon={<ShoppingBag className="h-12 w-12" />}
                            title="No products found"
                            description={`No products are currently available in the ${category.name} category.`}
                        />
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
