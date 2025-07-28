import { GlobalSearch } from '@/components/global-search';
import { ShoppingCartIcon } from '@/components/shopping-cart-icon';

export function NavMenu() {
    return (
        <div className="flex items-center">
            <GlobalSearch />
            <ShoppingCartIcon />
        </div>
    );
}
