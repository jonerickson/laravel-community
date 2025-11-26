import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import StoreIndexCategoriesItem from '@/components/store-index-categories-item';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { FolderIcon } from 'lucide-react';
import { route } from 'ziggy-js';

interface StoreCategoriesIndexProps {
    categories: App.Data.ProductCategoryData[];
}

export default function StoreCategoriesIndex({ categories }: StoreCategoriesIndexProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Store',
            href: route('store.index'),
        },
        {
            title: 'Categories',
            href: route('store.categories.index'),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Store - Categories">
                <meta name="description" content="Browse all product categories" />
                <meta property="og:title" content={`Store - Categories - ${siteName}`} />
                <meta property="og:description" content="Browse all product categories" />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                <Heading title="All product categories" description="Browse all product categories" />

                <div className="-mt-8">
                    {categories.length > 0 ? (
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {categories.map((category) => (
                                <StoreIndexCategoriesItem key={category.id} item={category} />
                            ))}
                        </div>
                    ) : (
                        <EmptyState icon={<FolderIcon />} title="No product categories" description="Check back later for more product options." />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
