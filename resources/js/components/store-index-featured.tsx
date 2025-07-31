import Heading from '@/components/heading';
import { type Product } from '@/types';
import { Link } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';

interface StoreFeaturedProps {
    products: Product[];
}

export default function StoreIndexFeatured({ products }: StoreFeaturedProps) {
    if (products.length === 0) {
        return null;
    }

    return (
        <div>
            <div className="sm:flex sm:items-baseline sm:justify-between">
                <Heading title="Featured Products" description="Our most popular products" />
            </div>

            <div className="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:grid-rows-2 sm:gap-x-6 lg:gap-8">
                {products.slice(0, 3).map((product, index) => (
                    <div
                        key={product.id}
                        className={`group relative aspect-[2/1] overflow-hidden rounded-lg ${
                            index === 0 ? 'sm:row-span-2 sm:aspect-square' : 'sm:aspect-auto'
                        }`}
                    >
                        {product.featured_image_url ? (
                            <img
                                alt={product.name}
                                src={product.featured_image_url}
                                className="absolute size-full object-cover group-hover:opacity-75"
                            />
                        ) : (
                            <div className="absolute flex size-full items-center justify-center bg-muted group-hover:opacity-75">
                                <ImageIcon className="h-16 w-16 text-muted-foreground" />
                            </div>
                        )}
                        <div aria-hidden="true" className="absolute inset-0 bg-gradient-to-b from-transparent to-black opacity-50" />
                        <div className="absolute inset-0 flex items-end p-6">
                            <div>
                                <h3 className="font-semibold text-white">
                                    <Link href={route('store.products.show', { slug: product.slug })}>
                                        <span className="absolute inset-0" />
                                        {product.name}
                                    </Link>
                                </h3>
                                <p aria-hidden="true" className="mt-1 text-sm text-white">
                                    Shop now
                                </p>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
