import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useApiRequest } from '@/hooks';
import { useCartOperations } from '@/hooks/use-cart-operations';
import AppLayout from '@/layouts/app-layout';
import { currency } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { stripCharacters } from '@/utils/truncate';
import { Deferred, Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Check, ImageIcon, LoaderCircle, ShoppingCart as ShoppingCartIcon, Ticket, Trash2, XIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { route } from 'ziggy-js';

interface ShoppingCartProps {
    cartItems: App.Data.CartItemData[];
    cartCount?: number;
    order?: App.Data.OrderData | null;
}

export default function ShoppingCart({ cartItems = [], order = null }: ShoppingCartProps) {
    const { auth } = usePage<App.Data.SharedData>().props;
    const { items, setItems, updateQuantity, removeItem, proceedToCheckout, calculateTotals, loading } = useCartOperations(cartItems);
    const [policiesAgreed, setPoliciesAgreed] = useState(false);
    const [discountCode, setDiscountCode] = useState('');
    const [appliedDiscount, setAppliedDiscount] = useState<App.Data.DiscountData | null>(
        order?.discounts && order.discounts.length > 0 ? order.discounts[0] : null,
    );
    const { delete: clearCartForm, processing: clearCartProcessing } = useForm();
    const { subtotal, total } = calculateTotals();
    const { loading: validatingDiscount, execute: validateDiscount } = useApiRequest();
    const { loading: removingDiscount, execute: removeDiscountRequest } = useApiRequest();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Store',
            href: route('store.index'),
        },
        {
            title: 'Shopping Cart',
            href: route('store.cart.index'),
        },
    ];

    useEffect(() => {
        const orderDiscount = order?.discounts && order.discounts.length > 0 ? order.discounts[0] : null;
        setAppliedDiscount(orderDiscount);

        if (orderDiscount) {
            setDiscountCode(orderDiscount.code);
        } else {
            setDiscountCode('');
        }
    }, [order]);

    const clearCart = () => {
        if (!window.confirm('Are you sure you want to empty your cart? This action cannot be undone.')) {
            return;
        }

        clearCartForm(route('store.cart.destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                setItems([]);

                window.dispatchEvent(
                    new CustomEvent('cart-updated', {
                        detail: {
                            cartCount: 0,
                            cartItems: [],
                        },
                    }),
                );
            },
        });
    };

    const getAllPolicies = (): App.Data.PolicyData[] => {
        const allPolicies: App.Data.PolicyData[] = [];
        const seenPolicyIds = new Set<number>();

        items.forEach((item) => {
            if (item.product?.policies) {
                item.product.policies.forEach((policy) => {
                    if (!seenPolicyIds.has(policy.id)) {
                        seenPolicyIds.add(policy.id);
                        allPolicies.push(policy);
                    }
                });
            }
        });

        return allPolicies.sort((a, b) => a.title.localeCompare(b.title));
    };

    const policies = getAllPolicies();

    const handleUpdateQuantity = (productId: number, quantity: number, priceId?: number | null) => {
        updateQuantity(productId, quantity, priceId);
    };

    const handleRemoveItem = (productId: number, priceId?: number | null) => {
        const item = items.find((i) => i.productId === productId);
        if (!item) return;

        removeItem(productId, item.name, priceId);
    };

    const handleApplyDiscount = async () => {
        if (!discountCode.trim()) {
            toast.error('Please enter a discount code.');
            return;
        }

        await validateDiscount(
            {
                url: route('api.discount.store'),
                method: 'POST',
                data: {
                    code: discountCode.trim(),
                    order_total: Math.round(total * 100),
                },
            },
            {
                onSuccess: () => router.reload({ only: ['order'] }),
            },
        );
    };

    const handleRemoveDiscount = async () => {
        if (!appliedDiscount) return;

        const discountToRemove = appliedDiscount;

        setAppliedDiscount(null);
        setDiscountCode('');

        await removeDiscountRequest(
            {
                url: route('api.discount.destroy'),
                method: 'POST',
                data: {
                    discount_id: discountToRemove.id,
                },
            },
            {
                onSuccess: () => router.reload({ only: ['order'] }),
                onError: () => {
                    setAppliedDiscount(discountToRemove);
                    setDiscountCode(discountToRemove.code);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Shopping cart" />

            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <div className="flex items-start justify-between">
                    <Heading title="Shopping cart" description={`${items.length} ${items.length === 1 ? 'item' : 'items'} in your cart`} />
                    {auth && auth.user && items.length > 0 && (
                        <Button variant="outline" onClick={clearCart} disabled={clearCartProcessing}>
                            {clearCartProcessing ? <LoaderCircle className="animate-spin" /> : <Trash2 />}
                            {clearCartProcessing ? 'Emptying...' : 'Empty cart'}
                        </Button>
                    )}
                </div>

                {items.length > 0 ? (
                    <form className="grid gap-6 lg:grid-cols-12 lg:items-start lg:gap-8">
                        <section aria-labelledby="cart-heading" className="lg:col-span-7">
                            <div className="sr-only" id="cart-heading">
                                <HeadingSmall title="Items in your shopping cart" />
                            </div>

                            <ul role="list" className="divide-y divide-border overflow-hidden rounded-xl">
                                {items.map((item) => (
                                    <li key={item.productId} className="relative flex flex-col items-start gap-4 bg-card p-6 sm:flex-row sm:gap-6">
                                        <div className="hidden shrink-0 sm:block">
                                            {item.product?.featuredImageUrl ? (
                                                <img
                                                    alt={item.name}
                                                    src={item.product.featuredImageUrl}
                                                    className="size-32 rounded-md object-cover sm:size-64"
                                                />
                                            ) : (
                                                <div className="flex size-32 items-center justify-center rounded-md bg-muted sm:size-64">
                                                    <ImageIcon className="h-8 w-8 text-muted-foreground sm:h-12 sm:w-12" />
                                                </div>
                                            )}
                                        </div>

                                        <div className="flex w-full flex-1 flex-col">
                                            <div className="relative flex h-full flex-row justify-between sm:flex-col sm:pr-0">
                                                <div className="flex flex-1 flex-col gap-3">
                                                    <div className="flex-grow">
                                                        <div className="flex justify-between">
                                                            <h3 className="text-sm">
                                                                <Link
                                                                    href={route('store.products.show', item.slug)}
                                                                    className="font-medium text-shadow-muted-foreground hover:text-shadow-muted"
                                                                >
                                                                    {item.name}
                                                                </Link>
                                                            </h3>
                                                        </div>
                                                        {item.product?.description && stripCharacters(item.product.description).length > 0 && (
                                                            <div className="mt-1 flex text-sm">
                                                                <p
                                                                    className="max-w-[90%] break-words text-muted-foreground sm:max-w-[85%]"
                                                                    dangerouslySetInnerHTML={{
                                                                        __html:
                                                                            (item.product?.description || '').length > 200
                                                                                ? `${item.product?.description?.substring(0, 200)}...`
                                                                                : item.product?.description || '',
                                                                    }}
                                                                ></p>
                                                            </div>
                                                        )}
                                                        <p className="mt-3 text-sm font-medium text-foreground">
                                                            {item.selectedPrice
                                                                ? `${currency(item.selectedPrice.amount)} ${item.selectedPrice.interval ? ` / ${item.selectedPrice.interval}` : ''}`
                                                                : item.product?.defaultPrice
                                                                  ? `${currency(item.product.defaultPrice.amount)} ${item.product.defaultPrice.interval ? ` / ${item.product.defaultPrice.interval}` : ''}`
                                                                  : 'Price TBD'}
                                                        </p>
                                                    </div>

                                                    <div className="mt-auto space-y-3">
                                                        {item.availablePrices && item.availablePrices.length > 1 && (
                                                            <div>
                                                                <label className="mb-2 block text-sm font-medium text-foreground">Price:</label>
                                                                <Select
                                                                    value={
                                                                        item.selectedPrice?.id?.toString() ||
                                                                        item.availablePrices.find((p) => p.isDefault)?.id?.toString() ||
                                                                        ''
                                                                    }
                                                                    onValueChange={(value) => {
                                                                        const newPriceId = parseInt(value);
                                                                        handleUpdateQuantity(item.productId, item.quantity, newPriceId);
                                                                    }}
                                                                >
                                                                    <SelectTrigger className="w-full">
                                                                        <SelectValue placeholder="Select price" />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        {item.availablePrices.map((price) => (
                                                                            <SelectItem key={price.id} value={price.id.toString()}>
                                                                                {price.name} - {currency(price.amount)}
                                                                                {price.interval && ` / ${price.interval}`}
                                                                            </SelectItem>
                                                                        ))}
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>
                                                        )}

                                                        <div className="w-full max-w-24">
                                                            <label className="mb-2 block text-sm font-medium text-foreground">Quantity:</label>
                                                            <Select
                                                                value={item.quantity.toString()}
                                                                onValueChange={(value) =>
                                                                    handleUpdateQuantity(item.productId, parseInt(value), item.priceId)
                                                                }
                                                                disabled={loading === item.productId}
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

                                                <div className="sm:absolute sm:top-0 sm:right-0">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleRemoveItem(item.productId, item.priceId)}
                                                        disabled={loading === item.productId}
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

                        <section aria-labelledby="summary-heading" className="relative rounded-lg bg-card p-4 sm:p-6 lg:col-span-5">
                            <HeadingSmall title="Order summary" />

                            <dl className="divide-shadow-muted divide-y">
                                <div className="flex items-center justify-between py-3">
                                    <dt className="text-sm text-muted-foreground">Subtotal</dt>
                                    <dd className="text-sm font-medium text-primary">{currency(subtotal)}</dd>
                                </div>

                                <div className="py-3">
                                    <dt className="mb-2 flex items-center gap-2">
                                        <Ticket className="size-4" />
                                        <h4 className="text-sm font-medium">Discount code</h4>
                                    </dt>
                                    {appliedDiscount ? (
                                        <dd className="space-y-2">
                                            <div className="flex items-center justify-between rounded-md bg-green-50 px-3 py-2 dark:bg-green-900/20">
                                                <div className="flex items-center gap-2">
                                                    <Check className="size-4 text-green-600 dark:text-green-400" />
                                                    <span className="font-mono text-sm font-medium">{appliedDiscount.code}</span>
                                                    <span className="text-xs text-muted-foreground">
                                                        (
                                                        {appliedDiscount.discountType === 'percentage'
                                                            ? `${appliedDiscount.value}%`
                                                            : currency(appliedDiscount.currentBalance)}
                                                        )
                                                    </span>
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={handleRemoveDiscount}
                                                    disabled={removingDiscount}
                                                    className="h-6 px-2 text-xs"
                                                >
                                                    {removingDiscount && <LoaderCircle className="animate-spin" />}
                                                    {removingDiscount ? 'Removing...' : 'Remove'}
                                                </Button>
                                            </div>
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-green-600 dark:text-green-400">Discount</span>
                                                <span className="font-medium text-green-600 dark:text-green-400">
                                                    -{currency(appliedDiscount.amountApplied ?? 0)}
                                                </span>
                                            </div>
                                        </dd>
                                    ) : (
                                        <dd className="flex gap-2">
                                            <Input
                                                type="text"
                                                placeholder="Enter code"
                                                value={discountCode}
                                                onChange={(e) => setDiscountCode(e.target.value.toUpperCase())}
                                                onKeyDown={(e) => {
                                                    if (e.key === 'Enter') {
                                                        e.preventDefault();
                                                        void handleApplyDiscount();
                                                    }
                                                }}
                                                className="flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                onClick={() => void handleApplyDiscount()}
                                                disabled={validatingDiscount || !discountCode.trim()}
                                            >
                                                {validatingDiscount && <LoaderCircle className="animate-spin" />}
                                                {validatingDiscount ? 'Applying...' : 'Apply'}
                                            </Button>
                                        </dd>
                                    )}
                                </div>

                                <div className="flex items-center justify-between py-3">
                                    <dt className="text-base font-medium text-muted-foreground">Order total</dt>
                                    <Deferred fallback={<LoaderCircle className="size-4 animate-spin text-muted-foreground" />} data={'order'}>
                                        <dd className="text-base font-medium text-primary">{currency(order?.amount)}</dd>
                                    </Deferred>
                                </div>

                                {policies.length > 0 && (
                                    <div className="py-3">
                                        <dt className="mb-3 flex items-center gap-2">
                                            <h4 className="sidebar-primary text-sm font-medium">Required Policies</h4>
                                        </dt>
                                        <dd className="mb-3 text-xs text-sidebar-accent-foreground">
                                            By proceeding with checkout, you agree to the following policies:
                                        </dd>
                                        <ul className="mb-4 space-y-2">
                                            {policies.map((policy) => (
                                                <li key={policy.id}>
                                                    <Link
                                                        href={
                                                            policy.category?.slug && policy.slug
                                                                ? route('policies.show', [policy.category.slug, policy.slug])
                                                                : '#'
                                                        }
                                                        className="text-xs text-blue-600 underline hover:text-blue-800"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        {policy.title}
                                                        {policy.version && ` (v${policy.version})`}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                        <div className="flex items-start space-x-2">
                                            <Checkbox
                                                id="policies-agreement"
                                                checked={policiesAgreed}
                                                onCheckedChange={(checked) => setPoliciesAgreed(checked === true)}
                                                className="mt-0.5"
                                            />
                                            <label
                                                htmlFor="policies-agreement"
                                                className="cursor-pointer text-xs leading-relaxed text-muted-foreground"
                                            >
                                                I agree to the above policies and understand that I must comply with them.
                                            </label>
                                        </div>
                                    </div>
                                )}
                            </dl>

                            <div className="mt-2">
                                <Button
                                    className="w-full"
                                    onClick={proceedToCheckout}
                                    disabled={loading !== null || (policies.length > 0 && !policiesAgreed)}
                                >
                                    {loading && <LoaderCircle className="animate-spin" />}
                                    {loading
                                        ? 'Processing...'
                                        : policies.length > 0 && !policiesAgreed
                                          ? 'Agree to policies to checkout'
                                          : 'Checkout'}
                                </Button>
                            </div>
                        </section>
                    </form>
                ) : (
                    <EmptyState
                        icon={<ShoppingCartIcon />}
                        title="Your cart is empty"
                        description="No items in your cart yet. Start shopping to add products to your cart."
                        buttonText="Continue Shopping"
                        onButtonClick={() => router.visit(route('store.index'))}
                    />
                )}
            </div>
        </AppLayout>
    );
}
