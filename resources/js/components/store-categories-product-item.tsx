import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Product, ProductCategory } from '@/types';
import { truncate } from '@/utils/truncate';
import { Link } from '@inertiajs/react';

export default function StoreCategoriesProductItem({ product, category }: { product: Product; category: ProductCategory }) {
    return (
        <div key={product.id} className="group relative flex flex-col p-4 sm:p-6">
            <img alt={product.name} src={product.featured_image_url || ''} className="aspect-square rounded-lg bg-gray-200 object-cover" />
            <div className="flex flex-1 flex-col pt-6 pb-4">
                <div className="flex-1">
                    <HeadingSmall title={product.name} description={truncate(product.description)} />
                    <div className="mt-3 flex flex-col items-center">
                        <p className="sr-only">{product.rating} out of 5 stars</p>
                        {/*<div className="flex items-center">*/}
                        {/*    {[0, 1, 2, 3, 4].map((rating) => (*/}
                        {/*        <StarIcon*/}
                        {/*            key={rating}*/}
                        {/*            aria-hidden="true"*/}
                        {/*            className={classNames(*/}
                        {/*                product.rating > rating ? 'text-yellow-400' : 'text-gray-200',*/}
                        {/*                'size-5 shrink-0',*/}
                        {/*            )}*/}
                        {/*        />*/}
                        {/*    ))}*/}
                        {/*</div>*/}
                        {/*<p className="mt-1 text-sm text-gray-500">{product.reviewCount} reviews</p>*/}
                    </div>
                    <p className="mt-4 text-base font-medium text-gray-900">${product.default_price?.amount || '0.00'}</p>
                </div>
                <Button className="mt-4 w-full" asChild>
                    <Link href={route('store.categories.products.show', { product: product.slug, category: category.slug })}>View</Link>
                </Button>
            </div>
        </div>
    );
}
