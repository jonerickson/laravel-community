import { GlobalSearch } from '@/components/global-search';
import { ShoppingCartIcon } from '@/components/shopping-cart-icon';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

export function NavMenu() {
    return (
        <div className="flex items-center">
            <Tooltip>
                <TooltipTrigger asChild>
                    <GlobalSearch />
                </TooltipTrigger>
                <TooltipContent>Search</TooltipContent>
            </Tooltip>
            <Tooltip>
                <TooltipTrigger asChild>
                    <ShoppingCartIcon />
                </TooltipTrigger>
                <TooltipContent>Shopping Cart</TooltipContent>
            </Tooltip>
        </div>
    );
}
