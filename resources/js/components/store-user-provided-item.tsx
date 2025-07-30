import HeadingSmall from '@/components/heading-small';
import { Product } from '@/types';
import { Link } from '@inertiajs/react';

export interface StoreUserProvidedItem extends Product {
    imageAlt?: string;
    imageUrl?: string;
}

export default function StoreUserProvidedItem({ item }: { item: StoreUserProvidedItem }) {
    return (
        <Link href={route('store.products.show', { slug: item.slug })} key={item.name} className="group relative">
            <img
                alt={item.imageAlt || item.name}
                src={item.imageUrl || item.featured_image_url || '/placeholder-image.jpg'}
                className="w-full rounded-lg bg-white object-cover group-hover:opacity-75 max-sm:h-80 sm:aspect-[2/1] lg:aspect-square"
            />
            <div className="mt-6 flex items-center justify-between">
                <HeadingSmall title={item.name} description={item.description} />
                <div className="mt-2 text-sm font-bold">$75</div>
            </div>
        </Link>
    );
}
