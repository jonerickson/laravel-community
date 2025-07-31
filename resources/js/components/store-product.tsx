import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { CartResponse, Product as ProductType } from '@/types';
import { ApiError, apiRequest } from '@/utils/api';
import axios from 'axios';
import { CurrencyIcon, GlobeIcon, ImageIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

const policies = [
    { name: 'International delivery', icon: GlobeIcon, description: 'Get your order in 2 years' },
    { name: 'Loyalty rewards', icon: CurrencyIcon, description: "Don't look at other tees" },
];

interface ProductProps {
    product: ProductType;
}

export default function Product({ product: productData }: ProductProps) {
    const [isAddingToCart, setIsAddingToCart] = useState(false);
    const [quantity, setQuantity] = useState(1);
    const [selectedPriceId, setSelectedPriceId] = useState<number | null>(productData?.default_price?.id || null);

    useEffect(() => {
        if (productData?.default_price && !selectedPriceId) {
            setSelectedPriceId(productData.default_price.id);
        }
    }, [selectedPriceId, productData?.default_price]);

    const addToCart = async () => {
        if (!productData) return;

        setIsAddingToCart(true);
        try {
            const data = await apiRequest<CartResponse>(
                axios.post(route('api.cart.store'), {
                    product_id: productData.id,
                    price_id: selectedPriceId,
                    quantity: quantity,
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
            console.error('API Error:', apiError.message);
            alert(apiError.message || 'Failed to add storeProduct to cart');
        } finally {
            setIsAddingToCart(false);
        }
    };

    return (
        <div className="sm:flex sm:items-baseline sm:justify-between">
            <div className="lg:grid lg:auto-rows-min lg:grid-cols-12 lg:gap-x-8">
                <div className="lg:col-span-5 lg:col-start-8">
                    <div className="flex justify-between">
                        <Heading
                            title={productData.name}
                            description={
                                productData
                                    ? productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price
                                        ? `$${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.amount} ${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.currency}${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.interval ? ` / ${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.interval}` : ''}`
                                        : 'Price TBD'
                                    : '0.00'
                            }
                        />
                    </div>
                    {/*<div>*/}
                    {/*    <h2 className="sr-only">Reviews</h2>*/}
                    {/*    <div className="flex items-center">*/}
                    {/*        <p className="text-sm text-muted-foreground">*/}
                    {/*            {productData.rating}*/}
                    {/*            <span className="sr-only"> out of 5 stars</span>*/}
                    {/*        </p>*/}
                    {/*        <div className="ml-1 flex items-center">*/}
                    {/*            {[0, 1, 2, 3, 4].map((rating) => (*/}
                    {/*                <StarIcon*/}
                    {/*                    key={rating}*/}
                    {/*                    aria-hidden="true"*/}
                    {/*                    className={cn(productData.rating > rating ? 'text-yellow-400' : 'text-gray-200', 'size-5 shrink-0')}*/}
                    {/*                />*/}
                    {/*            ))}*/}
                    {/*        </div>*/}
                    {/*    </div>*/}
                    {/*</div>*/}
                </div>

                <div className="mt-8 lg:col-span-7 lg:col-start-1 lg:row-span-3 lg:row-start-1 lg:mt-0">
                    <h2 className="sr-only">Images</h2>

                    <div className="grid grid-cols-1 lg:grid-cols-2 lg:grid-rows-3 lg:gap-8">
                        {productData?.featured_image_url ? (
                            <img alt={productData.name} src={productData.featured_image_url} className="col-span-2 row-span-2 rounded-lg" />
                        ) : (
                            <div className="col-span-2 row-span-2 flex min-h-[600px] items-center justify-center rounded-lg bg-muted">
                                <ImageIcon className="h-24 w-24 text-muted-foreground" />
                            </div>
                        )}
                    </div>
                </div>

                <div className="mt-8 lg:col-span-5">
                    <HeadingSmall title="Description" />
                    <p
                        dangerouslySetInnerHTML={{
                            __html: productData?.description,
                        }}
                        className="mt-4 text-sm text-muted-foreground"
                    />

                    {productData && (
                        <div className="mt-6 space-y-4">
                            {productData.prices && productData.prices.length > 0 && (
                                <div className="flex items-center gap-4">
                                    <label htmlFor="price" className="text-sm font-medium">
                                        Price:
                                    </label>
                                    <Select
                                        value={selectedPriceId?.toString() || ''}
                                        onValueChange={(value) => setSelectedPriceId(value ? parseInt(value) : null)}
                                        disabled={productData.prices.length === 1}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select price" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {productData.prices.map((price) => (
                                                <SelectItem key={price.id} value={price.id.toString()}>
                                                    {price.name} - ${price.amount} {price.currency}
                                                    {price.interval && ` / ${price.interval}`}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="flex items-center gap-4">
                                <label htmlFor="quantity" className="text-sm font-medium">
                                    Quantity:
                                </label>
                                <Select value={quantity.toString()} onValueChange={(value) => setQuantity(parseInt(value))}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Quantity" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((num) => (
                                            <SelectItem key={num} value={num.toString()}>
                                                {num}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    )}

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            addToCart();
                        }}
                    >
                        <Button type="submit" disabled={isAddingToCart || !productData} className="mt-8 flex w-full items-center justify-center">
                            {isAddingToCart ? 'Adding...' : 'Add to cart'}
                        </Button>
                    </form>

                    <div className="mt-8 border-t border-accent pt-8"></div>

                    <section aria-labelledby="policies-heading">
                        <h2 id="policies-heading" className="sr-only">
                            Our Policies
                        </h2>

                        <dl className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            {policies.map((policy) => (
                                <div key={policy.name} className="rounded-lg border border-border bg-accent p-6 text-center">
                                    <dt>
                                        <policy.icon aria-hidden="true" className="mx-auto size-6 shrink-0 text-sidebar-accent-foreground" />
                                        <div className="mt-2">
                                            <HeadingSmall title={policy.name} description={policy.description} />
                                        </div>
                                    </dt>
                                </div>
                            ))}
                        </dl>
                    </section>
                </div>
            </div>
        </div>
    );
}
