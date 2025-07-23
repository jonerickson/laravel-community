import { ProductCategory } from '@/types';
import { Link } from '@inertiajs/react';

export default function StoreCategoriesItem({ item }: { item: ProductCategory }) {
    return (
        <Link
            key={item.name}
            href={route('store.products.view', { id: item.id })}
            className="relative flex h-80 w-56 flex-col overflow-hidden rounded-lg p-6 hover:opacity-75 xl:w-auto"
        >
            <span aria-hidden="true" className="absolute inset-0">
                <img alt={item.imageAlt} src={item.imageUrl} className="size-full object-cover" />
            </span>
            <span aria-hidden="true" className="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-gray-800 opacity-50" />
            <span className="relative mt-auto text-center text-xl font-bold text-white">{item.name}</span>
        </Link>
    );
}
