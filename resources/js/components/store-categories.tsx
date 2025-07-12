import Heading from '@/components/heading';
import { Link } from '@inertiajs/react';

export default function StoreCategories({ categories }: { categories: unknown[] }) {
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
                                <Link
                                    key={category.name}
                                    href={route('store.products.view', { id: 1 })}
                                    className="relative flex h-80 w-56 flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 xl:w-auto"
                                >
                                    <span aria-hidden="true" className="absolute inset-0">
                                        <img alt="" src={category.imageSrc} className="size-full object-cover" />
                                    </span>
                                    <span
                                        aria-hidden="true"
                                        className="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-gray-800 opacity-50"
                                    />
                                    <span className="relative mt-auto text-center text-xl font-bold text-white">{category.name}</span>
                                </Link>
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
