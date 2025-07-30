import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, CartResponse, CheckoutResponse, Product, ProductPrice } from '@/types';
import { ApiError, apiRequest } from '@/utils/api';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { MessageCircleQuestionIcon, ShoppingCart as ShoppingCartIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

interface CartItem {
    product_id: number;
    price_id?: number | null;
    name: string;
    slug: string;
    quantity: number;
    product: Product | null;
    selected_price?: ProductPrice | null;
    available_prices?: ProductPrice[];
    added_at: string;
}

interface CartTotal {
    subtotal: number;
    tax: number;
    shipping: number;
    total: number;
}

interface ShoppingCartProps {
    cartItems: CartItem[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
];

export default function ShoppingCart({ cartItems = [] }: ShoppingCartProps) {
    const [items, setItems] = useState<CartItem[]>(cartItems);
    const [loading, setLoading] = useState<number | null>(null);
    const [checkoutLoading, setCheckoutLoading] = useState(false);

    // Calculate order totals
    const calculateTotals = () => {
        const subtotal = items.reduce((total, item) => {
            const price = item.selected_price || item.product?.default_price;
            if (price) {
                return total + price.amount * item.quantity;
            }
            return total;
        }, 0);

        const shipping = subtotal > 0 ? 5.0 : 0; // Free shipping over certain amount could be added
        const taxRate = 0.08; // 8% tax rate
        const tax = subtotal * taxRate;
        const total = subtotal + shipping + tax;

        return { subtotal, shipping, tax, total };
    };

    const { subtotal, shipping, tax, total } = calculateTotals();

    const updateQuantity = async (productId: number, quantity: number, priceId?: number | null) => {
        setLoading(productId);
        try {
            const data = await apiRequest<CartResponse>(
                axios.put(route('store.cart.update', productId), {
                    quantity: quantity,
                    price_id: priceId,
                }),
            );

            setItems(data.cartItems);
            window.dispatchEvent(
                new CustomEvent('cart-updated', {
                    detail: {
                        cartCount: data.cartCount,
                        cartItems: data.cartItems,
                    },
                }),
            );
        } catch (error) {
            console.error('Failed to update cart:', error);
            const apiError = error as ApiError;
            alert(apiError.message || 'Failed to update cart. Please try again.');
        } finally {
            setLoading(null);
        }
    };

    const removeItem = async (productId: number, priceId?: number | null) => {
        const item = items.find((i) => i.product_id === productId);
        const confirmed = window.confirm(`Are you sure you want to remove "${item?.name}" from your cart?`);

        if (!confirmed) {
            return;
        }

        setLoading(productId);
        try {
            const data = await apiRequest<CartResponse>(
                axios.delete(route('store.cart.delete', productId), {
                    data: { price_id: priceId },
                }),
            );

            setItems(data.cartItems);
            window.dispatchEvent(
                new CustomEvent('cart-updated', {
                    detail: {
                        cartCount: data.cartCount,
                        cartItems: data.cartItems,
                    },
                }),
            );
        } catch (error) {
            console.error('Failed to remove item:', error);
            const apiError = error as ApiError;
            alert(apiError.message || 'Failed to remove item. Please try again.');
        } finally {
            setLoading(null);
        }
    };

    const handleCheckout = async () => {
        setCheckoutLoading(true);
        try {
            const data = await apiRequest<CheckoutResponse>(axios.post(route('store.cart.checkout')));
            window.location.href = data.checkout_url;
        } catch (error) {
            console.error('Checkout failed:', error);
            const apiError = error as ApiError;
            alert(apiError.message || 'Checkout failed. Please try again.');
        } finally {
            setCheckoutLoading(false);
        }
    };

    if (items.length === 0) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Shopping Cart" />
                <div className="flex h-full flex-1 flex-col overflow-x-auto rounded-xl p-4">
                    <Heading title="Shopping Cart" description="Your cart is empty" />
                    <EmptyState
                        icon={<ShoppingCartIcon className="h-12 w-12" />}
                        title="Your cart is empty"
                        description="No items in your cart yet. Start shopping to add products to your cart."
                        buttonText="Continue Shopping"
                        onButtonClick={() => router.visit(route('store.categories'))}
                    />
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Shopping Cart" />
            <div className="flex h-full flex-1 flex-col overflow-x-auto rounded-xl p-4">
                <Heading title="Shopping Cart" description={`${items.length} ${items.length === 1 ? 'item' : 'items'} in your cart`} />
                <form className="lg:grid lg:grid-cols-12 lg:items-start lg:gap-x-12 xl:gap-x-16">
                    <section aria-labelledby="cart-heading" className="lg:col-span-7">
                        <div className="sr-only" id="cart-heading">
                            <HeadingSmall title="Items in your shopping cart" />
                        </div>

                        <ul role="list" className="divide-y divide-gray-200 border-t border-b border-gray-200">
                            {items.map((item) => (
                                <li key={item.product_id} className="flex py-6 sm:py-10">
                                    <div className="shrink-0">
                                        {item.product?.featured_image_url ? (
                                            <img
                                                alt={item.name}
                                                src={item.product.featured_image_url}
                                                className="size-32 rounded-md object-cover sm:size-64"
                                            />
                                        ) : (
                                            <div className="flex size-32 items-center justify-center rounded-md bg-gray-100 sm:size-64">
                                                <span className="text-gray-400">No image</span>
                                            </div>
                                        )}
                                    </div>

                                    <div className="ml-4 flex flex-1 flex-col sm:ml-6">
                                        <div className="relative flex h-full flex-col pr-9 sm:pr-0">
                                            <div className="flex flex-1 flex-col">
                                                <div className="flex-grow">
                                                    <div className="flex justify-between">
                                                        <h3 className="text-sm">
                                                            <Link
                                                                href={route('store.products.show', item.slug)}
                                                                className="font-medium text-gray-700 hover:text-gray-800"
                                                            >
                                                                {item.name}
                                                            </Link>
                                                        </h3>
                                                    </div>
                                                    <div className="mt-1 flex text-sm">
                                                        <p className="max-w-[90%] break-words text-gray-500 sm:max-w-[66%]">
                                                            {(item.product?.description || '').length > 200
                                                                ? `${item.product?.description?.substring(0, 200)}...`
                                                                : item.product?.description || ''}
                                                        </p>
                                                    </div>
                                                    <p className="mt-1 text-sm font-medium text-gray-900">
                                                        {item.selected_price
                                                            ? `$${item.selected_price.amount} ${item.selected_price.currency}${item.selected_price.interval ? ` / ${item.selected_price.interval}` : ''}`
                                                            : item.product?.default_price
                                                              ? `$${item.product.default_price.amount} ${item.product.default_price.currency}${item.product.default_price.interval ? ` / ${item.product.default_price.interval}` : ''}`
                                                              : 'Price TBD'}
                                                    </p>
                                                </div>

                                                <div className="mt-auto space-y-3">
                                                    {item.available_prices && item.available_prices.length > 1 && (
                                                        <div>
                                                            <label className="mb-1 block text-xs font-medium text-gray-700">Price:</label>
                                                            <Select
                                                                value={
                                                                    item.selected_price?.id?.toString() ||
                                                                    item.available_prices.find((p) => p.is_default)?.id?.toString() ||
                                                                    ''
                                                                }
                                                                onValueChange={(value) => {
                                                                    const newPriceId = parseInt(value);
                                                                    updateQuantity(item.product_id, item.quantity, newPriceId);
                                                                }}
                                                            >
                                                                <SelectTrigger className="w-full">
                                                                    <SelectValue placeholder="Select price" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {item.available_prices.map((price) => (
                                                                        <SelectItem key={price.id} value={price.id.toString()}>
                                                                            {price.name} - ${price.amount} {price.currency}
                                                                            {price.interval && ` / ${price.interval}`}
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                    )}

                                                    <div className="w-full max-w-24">
                                                        <label className="mb-1 block text-xs font-medium text-gray-700">Quantity:</label>
                                                        <Select
                                                            value={item.quantity.toString()}
                                                            onValueChange={(value) => updateQuantity(item.product_id, parseInt(value), item.price_id)}
                                                            disabled={loading === item.product_id}
                                                        >
                                                            <SelectTrigger className="w-full">
                                                                <SelectValue placeholder="Qty" />
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
                                            </div>

                                            <div className="absolute top-0 right-0">
                                                <button
                                                    type="button"
                                                    onClick={() => removeItem(item.product_id, item.price_id)}
                                                    disabled={loading === item.product_id}
                                                    className="-m-2 inline-flex p-2 text-gray-400 hover:text-gray-500 disabled:opacity-50"
                                                >
                                                    <span className="sr-only">Remove</span>
                                                    <XIcon aria-hidden="true" className="size-5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section aria-labelledby="summary-heading" className="mt-16 rounded-lg bg-gray-50 px-4 py-6 sm:p-6 lg:col-span-5 lg:mt-0 lg:p-8">
                        <HeadingSmall title="Order summary" />

                        <dl className="mt-6 space-y-4">
                            <div className="flex items-center justify-between">
                                <dt className="text-sm text-gray-600">Subtotal</dt>
                                <dd className="text-sm font-medium text-gray-900">${subtotal.toFixed(2)}</dd>
                            </div>
                            <div className="flex items-center justify-between border-t border-gray-200 pt-4">
                                <dt className="flex items-center text-sm text-gray-600">
                                    <span>Shipping estimate</span>
                                    <a href="#" className="ml-2 shrink-0 text-gray-400 hover:text-gray-500">
                                        <span className="sr-only">Learn more about how shipping is calculated</span>
                                        <MessageCircleQuestionIcon aria-hidden="true" className="size-5" />
                                    </a>
                                </dt>
                                <dd className="text-sm font-medium text-gray-900">{shipping > 0 ? `$${shipping.toFixed(2)}` : 'Free'}</dd>
                            </div>
                            <div className="flex items-center justify-between border-t border-gray-200 pt-4">
                                <dt className="flex text-sm text-gray-600">
                                    <span>Tax estimate</span>
                                    <a href="#" className="ml-2 shrink-0 text-gray-400 hover:text-gray-500">
                                        <span className="sr-only">Learn more about how tax is calculated</span>
                                        <MessageCircleQuestionIcon aria-hidden="true" className="size-5" />
                                    </a>
                                </dt>
                                <dd className="text-sm font-medium text-gray-900">${tax.toFixed(2)}</dd>
                            </div>
                            <div className="flex items-center justify-between border-t border-gray-200 pt-4">
                                <dt className="text-base font-medium text-gray-900">Order total</dt>
                                <dd className="text-base font-medium text-gray-900">${total.toFixed(2)}</dd>
                            </div>
                        </dl>

                        <div className="mt-6">
                            <Button className="w-full" onClick={handleCheckout} disabled={checkoutLoading}>
                                {checkoutLoading ? 'Processing...' : 'Checkout'}
                            </Button>
                        </div>
                    </section>
                </form>
            </div>
        </AppLayout>
    );
}
