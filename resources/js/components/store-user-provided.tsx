import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Link } from '@inertiajs/react';

export default function StoreUserProvided({ products }: { products: unknown[] }) {
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
                {products.map((callout) => (
                    <Link href={route('store.products.view', { id: 1 })} key={callout.name} className="group relative">
                        <img
                            alt={callout.imageAlt}
                            src={callout.imageSrc}
                            className="w-full rounded-lg bg-white object-cover group-hover:opacity-75 max-sm:h-80 sm:aspect-[2/1] lg:aspect-square"
                        />
                        <div className="mt-6 flex items-center justify-between">
                            <HeadingSmall title={callout.name} description={callout.description} />
                            <div className="mt-2 text-sm font-bold">$75</div>
                        </div>
                    </Link>
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
