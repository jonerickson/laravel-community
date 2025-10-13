export const getPriceDisplay = (product: App.Data.ProductData): string => {
    if (product.defaultPrice) {
        return `$${product.defaultPrice.amount.toFixed(2)}`;
    }

    if (product.prices && product.prices.length > 0) {
        const amounts = product.prices.map((price) => price.amount);
        const minPrice = Math.min(...amounts);
        const maxPrice = Math.max(...amounts);

        if (minPrice === maxPrice) {
            return `$${minPrice.toFixed(2)}`;
        }

        return `$${minPrice.toFixed(2)} - $${maxPrice.toFixed(2)}`;
    }

    return '$0.00';
};
