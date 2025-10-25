import { useApiRequest } from '@/hooks/use-api-request';
import { useState } from 'react';

interface CartTotals {
    subtotal: number;
    shipping: number;
    tax: number;
    total: number;
}

export function useCartOperations(initialItems: App.Data.CartItemData[] = []) {
    const [items, setItems] = useState<App.Data.CartItemData[]>(initialItems);
    const [loading, setLoading] = useState<number | null | boolean>(null);
    const { execute: executeApiRequest } = useApiRequest<App.Data.CartData>();
    const { execute: executeCheckout } = useApiRequest<App.Data.CheckoutData>();

    const updateQuantity = async (productId: number, quantity: number, priceId?: number | null) => {
        setLoading(productId);

        await executeApiRequest(
            {
                url: route('api.cart.update'),
                method: 'PUT',
                data: {
                    price_id: priceId,
                    quantity: quantity,
                },
            },
            {
                onSuccess: (data) => {
                    setItems(data.cartItems);

                    window.dispatchEvent(
                        new CustomEvent('cart-updated', {
                            detail: {
                                cartCount: data.cartCount,
                                cartItems: data.cartItems,
                            },
                        }),
                    );
                },
                onSettled: () => setLoading(null),
            },
        );
    };

    const addItem = async (productId: number, priceId: number | null, quantity: number) => {
        setLoading(productId);

        await executeApiRequest(
            {
                url: route('api.cart.store'),
                method: 'POST',
                data: {
                    price_id: priceId,
                    quantity: quantity,
                },
            },
            {
                onSuccess: (data) => {
                    setItems(data.cartItems);

                    window.dispatchEvent(
                        new CustomEvent('cart-updated', {
                            detail: {
                                cartCount: data.cartCount,
                                cartItems: data.cartItems,
                            },
                        }),
                    );
                },
                onSettled: () => setLoading(null),
            },
        );
    };

    const removeItem = async (productId: number, itemName: string, priceId?: number | null) => {
        if (!window.confirm(`Are you sure you want to remove "${itemName}" from your cart?`)) {
            return;
        }

        setLoading(productId);

        await executeApiRequest(
            {
                url: route('api.cart.destroy'),
                method: 'DELETE',
                data: {
                    price_id: priceId,
                },
            },
            {
                onSuccess: (data) => {
                    setItems(data.cartItems);

                    window.dispatchEvent(
                        new CustomEvent('cart-updated', {
                            detail: {
                                cartCount: data.cartCount,
                                cartItems: data.cartItems,
                            },
                        }),
                    );
                },
                onSettled: () => setLoading(null),
            },
        );
    };

    const proceedToCheckout = async () => {
        setLoading(true);

        await executeCheckout(
            {
                url: route('api.checkout'),
                method: 'POST',
            },
            {
                onSuccess: (data) => {
                    window.location.href = data.checkoutUrl;
                },
                onSettled: () => setLoading(null),
            },
        );
    };

    const calculateTotals = (): CartTotals => {
        const subtotal = items.reduce((total, item) => {
            const price = item.selectedPrice || item.product?.defaultPrice;
            if (price) {
                return total + price.amount * item.quantity;
            }
            return total;
        }, 0);

        const shipping = 0.0;
        const taxRate = 0.0;
        const tax = subtotal * taxRate;
        const total = subtotal + shipping + tax;

        return { subtotal, shipping, tax, total };
    };

    return {
        items,
        setItems,
        addItem,
        updateQuantity,
        removeItem,
        proceedToCheckout,
        calculateTotals,
        loading,
    };
}
