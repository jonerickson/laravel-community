import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { CartResponse, Product, ProductCategory } from '@/types';
import { ApiError, apiRequest } from '@/utils/api';
import { truncate } from '@/utils/truncate';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

export default function StoreCategoriesProductItem({ product, category }: { product: Product; category: ProductCategory }) {
    const [isAddingToCart, setIsAddingToCart] = useState(false);

    const addToCart = async () => {
        if (!product.default_price) return;

        setIsAddingToCart(true);
        try {
            const data = await apiRequest<CartResponse>(
                axios.post(route('api.cart.store'), {
                    product_id: product.id,
                    price_id: product.default_price.id,
                    quantity: 1,
                }),
            );

            window.dispatchEvent(
                new CustomEvent('cart-updated', {
                    detail: {
                        cartCount: data.cartCount,
                        cartItems: data.cartItems,
                    },
                }),
            );
        } catch (error) {
            console.error('Failed to add to cart:', error);
            const apiError = error as ApiError;
            alert(apiError.message || 'Failed to add product to cart');
        } finally {
            setIsAddingToCart(false);
        }
    };

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
                        {/*<p className="mt-1 text-sm text gray-500">{product.reviewCount} reviews</p>*/}
                    </div>
                </div>
                <div className="mt-4 space-y-2">
                    <p className="text-base font-medium text-primary">${product.default_price?.amount || '0.00'}</p>
                    <Button className="w-full" variant="outline" asChild>
                        <Link href={route('store.categories.products.show', { product: product.slug, category: category.slug })}>View</Link>
                    </Button>
                    <Button className="w-full" onClick={addToCart} disabled={isAddingToCart || !product.default_price}>
                        {isAddingToCart ? 'Adding...' : 'Add to cart'}
                    </Button>
                </div>
            </div>
        </div>
    );
}
