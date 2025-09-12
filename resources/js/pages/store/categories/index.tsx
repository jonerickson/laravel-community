import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import StoreIndexCategoriesItem from '@/components/store-index-categories-item';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, ProductCategory } from '@/types';
import { Head } from '@inertiajs/react';
import { FolderIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
    {
        title: 'Categories',
        href: '/store/categories',
    },
];

interface StoreCategoriesIndexProps {
    categories: ProductCategory[];
}

export default function StoreCategoriesIndex({ categories }: StoreCategoriesIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Store Categories" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                <Heading title="All product categories" description="Browse all product categories" />

                <div className="-mt-8">
                    {categories.length > 0 ? (
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                            {categories.map((category) => (
                                <StoreIndexCategoriesItem key={category.id} item={category} />
                            ))}
                        </div>
                    ) : (
                        <EmptyState icon={<FolderIcon />} title="No product categories" description="Check back later for new categories." />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
