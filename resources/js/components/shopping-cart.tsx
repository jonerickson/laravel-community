import { Button } from '@/components/ui/button';
import { ShoppingCartIcon } from 'lucide-react';

export default function ShoppingCart() {
    return (
        <>
            <Button data-sidebar="trigger" data-slot="sidebar-trigger" variant="ghost" size="icon" className="size-7">
                <ShoppingCartIcon />
                <span className="sr-only">Shopping cart</span>
            </Button>
        </>
    );
}
