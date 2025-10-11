import Heading from '@/components/heading';
import StoreIndexCategoriesItem from '@/components/store-index-categories-item';
import { Link } from '@inertiajs/react';
import { route } from 'ziggy-js';

export default function StoreIndexCategories({ categories }: { categories: App.Data.ProductCategoryData[] }) {
    return (
        <div>
            <div className="sm:flex sm:items-baseline sm:justify-between">
                <Heading title="Shop by category" description="Browse our most popular products" />
                <Link href={route('store.categories.index')} className="hidden text-sm font-semibold sm:block">
                    Browse all categories
                    <span aria-hidden="true"> &rarr;</span>
                </Link>
            </div>

            <div className="flow-root">
                <div className="-my-2">
                    <div className="h-full py-2">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                            {categories.map((category) => (
                                <StoreIndexCategoriesItem key={category.id} item={category} />
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            <div className="mt-6 sm:hidden">
                <Link href={route('store.categories.index')} className="block text-sm font-semibold">
                    Browse all categories
                    <span aria-hidden="true"> &rarr;</span>
                </Link>
            </div>
        </div>
    );
}
