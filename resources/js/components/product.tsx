import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { CartResponse, Product as ProductType } from '@/types';
import { ApiError, apiRequest } from '@/utils/api';
import axios from 'axios';
import { CurrencyIcon, GlobeIcon, StarIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

const product = {
    name: 'Basic Tee',
    price: '$35',
    rating: 3.9,
    reviewCount: 512,
    href: '#',
    breadcrumbs: [
        { id: 1, name: 'Women', href: '#' },
        { id: 2, name: 'Clothing', href: '#' },
    ],
    images: [
        {
            id: 1,
            imageSrc: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/product-page-01-featured-product-shot.jpg',
            imageAlt: "Back of women's Basic Tee in black.",
            primary: true,
        },
        {
            id: 2,
            imageSrc: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/product-page-01-product-shot-01.jpg',
            imageAlt: "Side profile of women's Basic Tee in black.",
            primary: false,
        },
        {
            id: 3,
            imageSrc: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/product-page-01-product-shot-02.jpg',
            imageAlt: "Front of women's Basic Tee in black.",
            primary: false,
        },
    ],
    colors: [
        { id: 'black', name: 'Black', classes: 'bg-gray-900 checked:outline-gray-900' },
        { id: 'heather-grey', name: 'Heather Grey', classes: 'bg-gray-400 checked:outline-gray-400' },
    ],
    sizes: [
        { id: 'xxs', name: 'XXS', inStock: true },
        { id: 'xs', name: 'XS', inStock: true },
        { id: 's', name: 'S', inStock: true },
        { id: 'm', name: 'M', inStock: true },
        { id: 'l', name: 'L', inStock: true },
        { id: 'xl', name: 'XL', inStock: false },
    ],
    description: `
    <p>The Basic tee is an honest new take on a classic. The tee uses super soft, pre-shrunk cotton for true comfort and a dependable fit. They are hand cut and sewn locally, with a special dye technique that gives each tee it's own look.</p>
    <p>Looking to stock your closet? The Basic tee also comes in a 3-pack or 5-pack at a bundle discount.</p>
  `,
    details: ['Only the best materials', 'Ethically and locally made', 'Pre-washed and pre-shrunk', 'Machine wash cold with similar colors'],
};
const policies = [
    { name: 'International delivery', icon: GlobeIcon, description: 'Get your order in 2 years' },
    { name: 'Loyalty rewards', icon: CurrencyIcon, description: "Don't look at other tees" },
];

interface ProductProps {
    product?: ProductType;
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
                axios.post(route('store.cart.store'), {
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
            alert(apiError.message || 'Failed to add product to cart');
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
                            title={productData?.name || product.name}
                            description={
                                productData
                                    ? productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price
                                        ? `$${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.amount} ${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.currency}${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.interval ? ` / ${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.interval}` : ''}`
                                        : 'Price TBD'
                                    : product.price
                            }
                        />
                    </div>
                    <div>
                        <h2 className="sr-only">Reviews</h2>
                        <div className="flex items-center">
                            <p className="text-sm text-muted-foreground">
                                {product.rating}
                                <span className="sr-only"> out of 5 stars</span>
                            </p>
                            <div className="ml-1 flex items-center">
                                {[0, 1, 2, 3, 4].map((rating) => (
                                    <StarIcon
                                        key={rating}
                                        aria-hidden="true"
                                        className={cn(product.rating > rating ? 'text-yellow-400' : 'text-gray-200', 'size-5 shrink-0')}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mt-8 lg:col-span-7 lg:col-start-1 lg:row-span-3 lg:row-start-1 lg:mt-0">
                    <h2 className="sr-only">Images</h2>

                    <div className="grid grid-cols-1 lg:grid-cols-2 lg:grid-rows-3 lg:gap-8">
                        {productData?.featured_image_url ? (
                            <img alt={productData.name} src={productData.featured_image_url} className="col-span-2 row-span-2 rounded-lg" />
                        ) : (
                            product.images.map((image) => (
                                <img
                                    key={image.id}
                                    alt={image.imageAlt}
                                    src={image.imageSrc}
                                    className={cn(image.primary ? 'lg:col-span-2 lg:row-span-2' : 'hidden lg:block', 'rounded-lg')}
                                />
                            ))
                        )}
                    </div>
                </div>

                <div className="mt-8 lg:col-span-5">
                    <HeadingSmall title="Description" />
                    <p
                        dangerouslySetInnerHTML={{
                            __html: productData?.description || product.description,
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
