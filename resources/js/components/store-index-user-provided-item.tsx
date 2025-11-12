import HeadingSmall from '@/components/heading-small';
import { Link } from '@inertiajs/react';

export default function StoreUserProvidedItem({ item }: { item: App.Data.ProductData }) {
    return (
        <Link href={route('store.products.show', { slug: item.slug })} key={item.name} className="group relative">
            <img
                alt={item.name}
                src={item.featuredImageUrl || '/placeholder-image.jpg'}
                className="w-full rounded-lg bg-white object-cover group-hover:opacity-75 max-sm:h-80 sm:aspect-[2/1] lg:aspect-square"
            />
            <div className="mt-6 flex items-center justify-between">
                <HeadingSmall title={item.name} description={item.description || ''} />
                <div className="mt-2 text-sm font-bold">$75</div>
            </div>
        </Link>
    );
}
