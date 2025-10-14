import { currency } from '@/lib/utils';

export const getPriceDisplay = (product: App.Data.ProductData): string => {
    if (product.defaultPrice) {
        return currency(product.defaultPrice.amount);
    }

    if (product.prices && product.prices.length > 0) {
        const amounts = product.prices.map((price) => price.amount);
        const minPrice = Math.min(...amounts);
        const maxPrice = Math.max(...amounts);

        if (minPrice === maxPrice) {
            return currency(minPrice);
        }

        return `${currency(minPrice)} - ${currency(maxPrice)}`;
    }

    return currency(0);
};
