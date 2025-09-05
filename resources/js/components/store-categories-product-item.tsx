import HeadingSmall from '@/components/heading-small';
import { StarRating } from '@/components/star-rating';
import { Button } from '@/components/ui/button';
import { useCartOperations } from '@/hooks/use-cart-operations';
import { Product, ProductCategory } from '@/types';
import { stripCharacters, truncate } from '@/utils/truncate';
import { Link, router } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';

export default function StoreCategoriesProductItem({ product }: { product: Product; category: ProductCategory }) {
    const { addItem, loading } = useCartOperations();

    const handleAddToCart = async () => {
        if (!product.default_price) {
            // If no default price, redirect to product page to select price
            router.visit(route('store.products.show', { product: product.slug }));
            return;
        }

        await addItem(product.id, product.default_price.id, 1);
    };

    const getPriceDisplay = () => {
        if (product.default_price) {
            return `$${product.default_price.amount}`;
        }

        if (product.prices && product.prices.length > 0) {
            const amounts = product.prices.map((price) => parseFloat(price.amount.toString()));
            const minPrice = Math.min(...amounts);
            const maxPrice = Math.max(...amounts);

            if (minPrice === maxPrice) {
                return `$${minPrice.toFixed(2)}`;
            }

            return `$${minPrice.toFixed(2)} - $${maxPrice.toFixed(2)}`;
        }

        return '$0.00';
    };

    return (
        <div key={product.id} className="group relative flex flex-col p-4 sm:p-6">
            {product.featured_image_url ? (
                <img alt={product.name} src={product.featured_image_url} className="aspect-square rounded-lg object-cover" />
            ) : (
                <div className="flex aspect-square items-center justify-center rounded-lg bg-muted">
                    <ImageIcon className="h-12 w-12 text-muted-foreground" />
                </div>
            )}
            <div className="flex flex-1 flex-col pt-6 pb-4">
                <div className="flex-1">
                    <HeadingSmall title={product.name} description={truncate(stripCharacters(product.description))} />
                    <div className="mt-3">
                        <StarRating rating={product.average_rating || 0} size="sm" className="mb-1" />
                    </div>
                </div>
                <div className="mt-4 space-y-2">
                    <p className="text-base font-medium text-primary">{getPriceDisplay()}</p>
                    <Button className="w-full" variant="outline" asChild>
                        <Link href={route('store.products.show', { product: product.slug })}>View</Link>
                    </Button>
                    <Button className="w-full" onClick={handleAddToCart} disabled={loading === product.id}>
                        {loading === product.id ? 'Adding...' : product.default_price ? 'Add to cart' : 'Select options'}
                    </Button>
                </div>
            </div>
        </div>
    );
}
