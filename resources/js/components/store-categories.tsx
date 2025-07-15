import Heading from '@/components/heading';
import StoreCategoriesItem, { StoreCategoryItem } from '@/components/store-categories-item';
import { Link } from '@inertiajs/react';

export default function StoreCategories({ categories }: { categories: StoreCategoryItem[] }) {
    return (
        <div>
            <div className="sm:flex sm:items-baseline sm:justify-between">
                <Heading title="Shop by category" description="Browse our most popular products" />
                <Link href="#" className="hidden text-sm font-semibold sm:block">
                    Browse all categories
                    <span aria-hidden="true"> &rarr;</span>
                </Link>
            </div>

            <div className="flow-root">
                <div className="-my-2">
                    <div className="relative box-content h-80 overflow-x-auto py-2 xl:overflow-visible">
                        <div className="absolute flex space-x-8 px-4 sm:px-6 lg:px-8 xl:relative xl:grid xl:grid-cols-5 xl:gap-x-8 xl:space-x-0 xl:px-0">
                            {categories.map((category) => (
                                <StoreCategoriesItem key={category.id} item={category} />
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            <div className="mt-6 sm:hidden">
                <Link href="#" className="block text-sm font-semibold">
                    Browse all categories
                    <span aria-hidden="true"> &rarr;</span>
                </Link>
            </div>
        </div>
    );
}
