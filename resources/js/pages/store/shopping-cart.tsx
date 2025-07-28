import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Product } from '@/types';
import { ApiError, apiRequest } from '@/utils/api';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { ArrowDownIcon, MessageCircleQuestionIcon, ShoppingCart as ShoppingCartIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

interface CartItem {
    product_id: number;
    name: string;
    slug: string;
    quantity: number;
    product: Product | null;
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

    const updateQuantity = async (productId: number, quantity: number) => {
        setLoading(productId);
        try {
            const response = await axios.put(route('store.cart.update', productId), { quantity: quantity });

            if (response.data.success) {
                setItems(response.data.data.cartItems);
                window.dispatchEvent(
                    new CustomEvent('cart-updated', {
                        detail: {
                            cartCount: response.data.data.cartCount,
                            cartItems: response.data.data.cartItems,
                        },
                    }),
                );
            } else {
                console.error('Failed to update cart:', response.data.message);
                alert(response.data.message || 'Failed to update cart');
            }
        } catch (error) {
            console.error('Failed to update cart:', error);
            if (axios.isAxiosError(error) && error.response?.data?.message) {
                alert(error.response.data.message);
            } else {
                alert('Failed to update cart. Please try again.');
            }
        } finally {
            setLoading(null);
        }
    };

    const removeItem = async (productId: number) => {
        setLoading(productId);
        try {
            const response = await axios.delete(route('store.cart.delete', productId));

            if (response.data.success) {
                setItems(response.data.data.cartItems);
                window.dispatchEvent(
                    new CustomEvent('cart-updated', {
                        detail: {
                            cartCount: response.data.data.cartCount,
                            cartItems: response.data.data.cartItems,
                        },
                    }),
                );
            } else {
                console.error('Failed to remove item:', response.data.message);
                alert(response.data.message || 'Failed to remove item');
            }
        } catch (error) {
            console.error('Failed to remove item:', error);
            if (axios.isAxiosError(error) && error.response?.data?.message) {
                alert(error.response.data.message);
            } else {
                alert('Failed to remove item. Please try again.');
            }
        } finally {
            setLoading(null);
        }
    };

    const handleCheckout = async () => {
        setCheckoutLoading(true);
        try {
            const data = await apiRequest(axios.post(route('store.cart.checkout')));
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
                            {items.map((item, itemIdx) => (
                                <li key={item.product_id} className="flex py-6 sm:py-10">
                                    <div className="shrink-0">
                                        {item.product?.featured_image_url ? (
                                            <img
                                                alt={item.name}
                                                src={item.product.featured_image_url}
                                                className="size-24 rounded-md object-cover sm:size-48"
                                            />
                                        ) : (
                                            <div className="flex size-24 items-center justify-center rounded-md bg-gray-100 sm:size-48">
                                                <span className="text-gray-400">No image</span>
                                            </div>
                                        )}
                                    </div>

                                    <div className="ml-4 flex flex-1 flex-col justify-between sm:ml-6">
                                        <div className="relative pr-9 sm:pr-0">
                                            <div className="flex h-full flex-col justify-between sm:h-24 lg:h-48">
                                                <div>
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
                                                    <p className="mt-1 text-sm font-medium text-gray-900">Price TBD</p>
                                                </div>

                                                <div className="mt-4 flex items-end justify-between sm:mt-0">
                                                    <div className="grid w-full max-w-16 grid-cols-1">
                                                        <select
                                                            value={item.quantity}
                                                            onChange={(e) => updateQuantity(item.product_id, parseInt(e.target.value))}
                                                            disabled={loading === item.product_id}
                                                            name={`quantity-${itemIdx}`}
                                                            aria-label={`Quantity, ${item.name}`}
                                                            className="col-start-1 row-start-1 appearance-none rounded-md bg-white py-1.5 pr-8 pl-3 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 disabled:opacity-50 sm:text-sm/6"
                                                        >
                                                            {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((num) => (
                                                                <option key={num} value={num}>
                                                                    {num}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <ArrowDownIcon
                                                            aria-hidden="true"
                                                            className="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 sm:size-4"
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="absolute top-0 right-0">
                                                <button
                                                    type="button"
                                                    onClick={() => removeItem(item.product_id)}
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
                                <dd className="text-sm font-medium text-gray-900">$99.00</dd>
                            </div>
                            <div className="flex items-center justify-between border-t border-gray-200 pt-4">
                                <dt className="flex items-center text-sm text-gray-600">
                                    <span>Shipping estimate</span>
                                    <a href="#" className="ml-2 shrink-0 text-gray-400 hover:text-gray-500">
                                        <span className="sr-only">Learn more about how shipping is calculated</span>
                                        <MessageCircleQuestionIcon aria-hidden="true" className="size-5" />
                                    </a>
                                </dt>
                                <dd className="text-sm font-medium text-gray-900">$5.00</dd>
                            </div>
                            <div className="flex items-center justify-between border-t border-gray-200 pt-4">
                                <dt className="flex text-sm text-gray-600">
                                    <span>Tax estimate</span>
                                    <a href="#" className="ml-2 shrink-0 text-gray-400 hover:text-gray-500">
                                        <span className="sr-only">Learn more about how tax is calculated</span>
                                        <MessageCircleQuestionIcon aria-hidden="true" className="size-5" />
                                    </a>
                                </dt>
                                <dd className="text-sm font-medium text-gray-900">$8.32</dd>
                            </div>
                            <div className="flex items-center justify-between border-t border-gray-200 pt-4">
                                <dt className="text-base font-medium text-gray-900">Order total</dt>
                                <dd className="text-base font-medium text-gray-900">$112.32</dd>
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
