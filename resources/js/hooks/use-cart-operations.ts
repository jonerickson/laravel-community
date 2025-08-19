import { useApiRequest } from '@/hooks/use-api-request';
import type { CartResponse } from '@/types';
import { useState } from 'react';
import { toast } from 'sonner';

interface CartTotals {
    subtotal: number;
    shipping: number;
    tax: number;
    total: number;
}

export function useCartOperations(initialItems: CartResponse['cartItems'] = []) {
    const [items, setItems] = useState<CartResponse['cartItems']>(initialItems);
    const [loading, setLoading] = useState<number | null>(null);
    const { execute: executeApiRequest } = useApiRequest<CartResponse>();

    const updateQuantity = async (productId: number, quantity: number, priceId?: number | null) => {
        setLoading(productId);

        await executeApiRequest(
            {
                url: route('api.cart.update'),
                method: 'PUT',
                data: {
                    product_id: productId,
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

                    toast.success('Your cart has been successfully updated.');
                },
                onSettled: () => {
                    setLoading(null);
                },
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
                    product_id: productId,
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

                    toast.success('The item has been successfully added to your cart.');
                },
                onSettled: () => {
                    setLoading(null);
                },
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
                    product_id: productId,
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

                    toast.success('The item has been successfully removed from your cart.');
                },
                onSettled: () => {
                    setLoading(null);
                },
            },
        );
    };

    const calculateTotals = (): CartTotals => {
        const subtotal = items.reduce((total, item) => {
            const price = item.selected_price || item.product?.default_price;
            if (price) {
                return total + price.amount * item.quantity;
            }
            return total;
        }, 0);

        const shipping = subtotal > 0 ? 5.0 : 0;
        const taxRate = 0.08;
        const tax = subtotal * taxRate;
        const total = subtotal + shipping + tax;

        return { subtotal, shipping, tax, total };
    };

    return {
        items,
        addItem,
        updateQuantity,
        removeItem,
        calculateTotals,
        loading,
    };
}
