import Heading from '@/components/heading';
import type { StoreUserProvidedItem as StoreUserProvidedItemData } from '@/components/store-user-provided-item';
import StoreUserProvidedItem from '@/components/store-user-provided-item';
import { Link } from '@inertiajs/react';

export default function StoreUserProvided({ products }: { products: StoreUserProvidedItemData[] }) {
    return (
        <div>
            <div className="sm:flex sm:items-baseline sm:justify-between">
                <Heading title="Community products" description="Browse products provided by our community" />
                <a href="#" className="hidden text-sm font-semibold sm:block">
                    Browse all community products
                    <span aria-hidden="true"> &rarr;</span>
                </a>
            </div>

            <div className="space-y-12 lg:grid lg:grid-cols-3 lg:space-y-0 lg:gap-x-6">
                {products.map((item) => (
                    <StoreUserProvidedItem key={item.id} item={item} />
                ))}
            </div>

            <div className="mt-6 sm:hidden">
                <Link href="#" className="block text-sm font-semibold">
                    Browse all community products
                    <span aria-hidden="true"> &rarr;</span>
                </Link>
            </div>
        </div>
    );
}
