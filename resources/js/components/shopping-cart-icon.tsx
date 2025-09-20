import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { ShoppingCart } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ShoppingCartIconProps {
    className?: string;
}

export function ShoppingCartIcon({ className }: ShoppingCartIconProps) {
    const page = usePage<App.Data.SharedData>();
    const initialCount = page.props.cartCount || 0;
    const [cartCount, setCartCount] = useState(initialCount);

    useEffect(() => {
        setCartCount(page.props.cartCount || 0);
    }, [page.props.cartCount]);

    useEffect(() => {
        const handleCartUpdate = (event: CustomEvent) => {
            setCartCount(event.detail.cartCount || 0);
        };

        window.addEventListener('cart-updated', handleCartUpdate as EventListener);

        return () => {
            window.removeEventListener('cart-updated', handleCartUpdate as EventListener);
        };
    }, []);

    return (
        <Link href={route('store.cart.index')} className={cn('relative', className)}>
            <Button variant="ghost" size="icon" className="relative h-9 w-9">
                <ShoppingCart className="size-5 opacity-80 group-hover:opacity-100" />
                {cartCount > 0 && (
                    <Badge
                        variant="destructive"
                        className="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full p-0 text-xs font-medium"
                    >
                        {cartCount > 99 ? '99+' : cartCount}
                    </Badge>
                )}
                <span className="sr-only">Shopping cart ({cartCount} items)</span>
            </Button>
        </Link>
    );
}
